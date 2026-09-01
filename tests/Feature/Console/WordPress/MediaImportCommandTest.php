<?php

use App\Models\MediaAsset;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function () {
    Storage::fake('public');
    config()->set('media-library.disk_name', 'public');
    $this->manifestPath = storage_path('app/wordpress-media-import-'.Str::uuid().'.json');
});

afterEach(function () {
    File::delete($this->manifestPath);
});

test('it previews selected media without writing records files or mappings', function () {
    writeWordPressManifest($this->manifestPath, [
        'source' => [
            'media_endpoint' => 'https://legacy.example/wp-json/wp/v2/media',
        ],
        'media' => [
            ['wordpress_id' => 49925, 'decision' => 'import'],
            ['wordpress_id' => 49926, 'decision' => 'skip', 'reason' => 'Niet gebruikt door geselecteerde content.'],
        ],
    ]);

    Http::fake([
        'legacy.example/wp-json/wp/v2/media/49925' => Http::response(
            wordpressMediaRecord(49925, altText: ''),
        ),
    ]);

    $exitCode = Artisan::call('wordpress:import', [
        'phase' => 'media',
        '--manifest' => $this->manifestPath,
        '--dry-run' => true,
    ]);

    expect($exitCode)->toBe(0)
        ->and(Artisan::output())
        ->toContain('Dry-run', '49925', 'klaar', '49926', 'overgeslagen', 'Ontbrekende alt-tekst: 1')
        ->and(MediaAsset::query()->count())->toBe(0)
        ->and(Storage::disk('public')->allFiles())->toBeEmpty()
        ->and(json_decode(File::get($this->manifestPath), true))->not->toHaveKey('mappings');
});

test('it imports media once and reuses the manifest mapping on repeated runs', function () {
    writeWordPressManifest($this->manifestPath, [
        'source' => [
            'media_endpoint' => 'https://legacy.example/wp-json/wp/v2/media',
        ],
        'media' => [
            ['wordpress_id' => 49925, 'decision' => 'import'],
        ],
    ]);

    Http::fake([
        'legacy.example/wp-json/wp/v2/media/49925' => Http::response(
            wordpressMediaRecord(
                49925,
                altText: 'Drone vliegt door een verlichte gate',
                caption: '<p>Finale tijdens de wintertraining.</p>',
            ),
        ),
        'legacy.example/wp-content/uploads/2025/09/race-cover.png' => Http::response(
            onePixelPng(),
            headers: ['Content-Type' => 'image/png'],
        ),
    ]);

    $this->pendingArtisan('wordpress:import', [
        'phase' => 'media',
        '--manifest' => $this->manifestPath,
    ])
        ->expectsOutputToContain('geïmporteerd')
        ->assertSuccessful();

    $mediaAsset = MediaAsset::query()->with('media')->sole();
    $manifest = json_decode(File::get($this->manifestPath), true, flags: JSON_THROW_ON_ERROR);
    $mapping = data_get($manifest, 'mappings.media.49925');

    expect($mediaAsset)
        ->alt_text->toBe(['nl' => 'Drone vliegt door een verlichte gate'])
        ->and($mediaAsset->filename())->toBe('race-cover.png')
        ->and($mediaAsset->mimeType())->toBe('image/png')
        ->and($mediaAsset->width())->toBe(1)
        ->and($mediaAsset->height())->toBe(1)
        ->and($mediaAsset->url())->not->toBeEmpty()
        ->and(Storage::disk('public')->exists($mediaAsset->storagePath()))->toBeTrue()
        ->and($mapping)->toMatchArray([
            'media_asset_id' => $mediaAsset->getKey(),
            'source_url' => 'https://legacy.example/wp-content/uploads/2025/09/race-cover.png',
            'original_filename' => 'race-cover.png',
            'mime_type' => 'image/png',
            'size_bytes' => strlen(onePixelPng()),
            'width' => 1,
            'height' => 1,
            'alt_text' => 'Drone vliegt door een verlichte gate',
            'caption' => 'Finale tijdens de wintertraining.',
        ])
        ->and($mapping['checksum_sha256'])->toBe(hash('sha256', onePixelPng()));

    $this->pendingArtisan('wordpress:import', [
        'phase' => 'media',
        '--manifest' => $this->manifestPath,
    ])
        ->expectsOutputToContain('hergebruikt')
        ->assertSuccessful();

    expect(MediaAsset::query()->count())->toBe(1)
        ->and(Storage::disk('public')->allFiles())->toHaveCount(1);

    Http::assertSentCount(2);
});

test('it imports reviewed manifest alt text when the WordPress source has none', function () {
    writeWordPressManifest($this->manifestPath, [
        'source' => [
            'media_endpoint' => 'https://legacy.example/wp-json/wp/v2/media',
        ],
        'media' => [
            [
                'wordpress_id' => 49925,
                'decision' => 'import',
                'alt_text' => '  <strong>FPV-racedrone</strong> vliegt door een verlichte gate &amp; finisht  ',
            ],
        ],
    ]);

    Http::fake([
        'legacy.example/wp-json/wp/v2/media/49925' => Http::response(
            wordpressMediaRecord(49925, altText: ''),
        ),
        'legacy.example/wp-content/uploads/2025/09/race-cover.png' => Http::response(
            onePixelPng(),
            headers: ['Content-Type' => 'image/png'],
        ),
    ]);

    $exitCode = Artisan::call('wordpress:import', [
        'phase' => 'media',
        '--manifest' => $this->manifestPath,
    ]);

    $mediaAsset = MediaAsset::query()->sole();
    $manifest = json_decode(File::get($this->manifestPath), true, flags: JSON_THROW_ON_ERROR);

    expect($exitCode)->toBe(0)
        ->and(Artisan::output())->not->toContain('Ontbrekende alt-tekst')
        ->and($mediaAsset->alt_text)->toBe([
            'nl' => 'FPV-racedrone vliegt door een verlichte gate & finisht',
        ])->and(data_get($manifest, 'mappings.media.49925.alt_text'))
        ->toBe('FPV-racedrone vliegt door een verlichte gate & finisht');
});

test('it reports failed downloads and unsupported file types', function () {
    writeWordPressManifest($this->manifestPath, [
        'source' => [
            'media_endpoint' => 'https://legacy.example/wp-json/wp/v2/media',
        ],
        'media' => [
            ['wordpress_id' => 501, 'decision' => 'import'],
            ['wordpress_id' => 502, 'decision' => 'import'],
        ],
    ]);

    Http::fake([
        'legacy.example/wp-json/wp/v2/media/501' => Http::response(
            wordpressMediaRecord(501),
        ),
        'legacy.example/wp-content/uploads/2025/09/race-cover.png' => Http::response(
            status: 503,
        ),
        'legacy.example/wp-json/wp/v2/media/502' => Http::response(
            wordpressMediaRecord(
                502,
                sourceUrl: 'https://legacy.example/wp-content/uploads/archive.zip',
                filename: 'archive.zip',
                mimeType: 'application/zip',
            ),
        ),
    ]);

    $this->pendingArtisan('wordpress:import', [
        'phase' => 'media',
        '--manifest' => $this->manifestPath,
    ])
        ->expectsOutputToContain('Download mislukt met HTTP 503')
        ->expectsOutputToContain('Niet-ondersteund bestandstype: application/zip')
        ->assertFailed();

    expect(MediaAsset::query()->count())->toBe(0)
        ->and(Storage::disk('public')->allFiles())->toBeEmpty();
});

test('it rejects invalid manifests before making requests', function () {
    writeWordPressManifest($this->manifestPath, [
        'source' => [
            'media_endpoint' => 'file:///tmp/media',
        ],
        'media' => [],
    ]);

    $this->pendingArtisan('wordpress:import', [
        'phase' => 'media',
        '--manifest' => $this->manifestPath,
    ])
        ->expectsOutputToContain('geldige HTTP(S)-URL')
        ->assertFailed();

    Http::assertNothingSent();
});

test('it rejects unreadable media manifests', function (string $contents, string $message) {
    File::put($this->manifestPath, $contents);

    $this->pendingArtisan('wordpress:import', [
        'phase' => 'media',
        '--manifest' => $this->manifestPath,
    ])
        ->expectsOutputToContain($message)
        ->assertFailed();

    Http::assertNothingSent();
})->with([
    'invalid JSON' => ['{', 'Syntax error'],
    'non-object JSON' => ['null', 'JSON-object'],
]);

test('it rejects invalid media selections', function (Closure $mutate, string $message) {
    $manifest = [
        'source' => ['media_endpoint' => 'https://legacy.example/wp-json/wp/v2/media/'],
        'media' => [['wordpress_id' => 49925, 'decision' => 'import']],
    ];
    $mutate($manifest);
    writeWordPressManifest($this->manifestPath, $manifest);

    $this->pendingArtisan('wordpress:import', [
        'phase' => 'media',
        '--manifest' => $this->manifestPath,
    ])
        ->expectsOutputToContain($message)
        ->assertFailed();

    Http::assertNothingSent();
})->with([
    'non-list media' => [
        fn (array &$manifest) => $manifest['media'] = ['selection' => []],
        'media-lijst',
    ],
    'non-object selection' => [
        fn (array &$manifest) => $manifest['media'] = ['invalid'],
        'Media-selectie 0 moet een JSON-object zijn.',
    ],
    'invalid WordPress ID' => [
        fn (array &$manifest) => $manifest['media'][0]['wordpress_id'] = 0,
        'geldige wordpress_id',
    ],
    'duplicate WordPress ID' => [
        fn (array &$manifest) => $manifest['media'][] = $manifest['media'][0],
        'staat dubbel',
    ],
    'invalid decision' => [
        fn (array &$manifest) => $manifest['media'][0]['decision'] = 'rewrite',
        'ongeldige decision',
    ],
    'invalid reason' => [
        fn (array &$manifest) => $manifest['media'][0]['reason'] = 123,
        'ongeldige reason',
    ],
    'invalid reviewed alt text' => [
        fn (array &$manifest) => $manifest['media'][0]['alt_text'] = ['nl' => 'Race drone'],
        'ongeldige alt_text',
    ],
    'empty reviewed alt text' => [
        fn (array &$manifest) => $manifest['media'][0]['alt_text'] = ' <strong> </strong> ',
        'lege alt_text',
    ],
]);

test('it reports malformed WordPress media metadata', function (Closure $mutate, string $message) {
    writeWordPressManifest($this->manifestPath, [
        'source' => ['media_endpoint' => 'https://legacy.example/wp-json/wp/v2/media'],
        'media' => [['wordpress_id' => 49925, 'decision' => 'import']],
    ]);
    $record = wordpressMediaRecord(49925);
    $mutate($record);
    Http::fake([
        'legacy.example/wp-json/wp/v2/media/49925' => Http::response($record),
    ]);

    $this->pendingArtisan('wordpress:import', [
        'phase' => 'media',
        '--manifest' => $this->manifestPath,
        '--dry-run' => true,
    ])
        ->expectsOutputToContain($message)
        ->assertFailed();
})->with([
    'unexpected ID' => [
        fn (array &$record) => $record['id'] = 1,
        'onverwacht ID',
    ],
    'invalid source URL' => [
        fn (array &$record) => $record['source_url'] = 'file:///tmp/image.png',
        'geen geldige download-URL',
    ],
    'missing MIME type' => [
        fn (array &$record) => $record['mime_type'] = '',
        'geen MIME-type',
    ],
    'missing filename' => [
        function (array &$record) {
            $record['source_url'] = 'https://legacy.example';
            $record['media_details']['file'] = '';
        },
        'geen bruikbare bestandsnaam',
    ],
]);

test('it reuses mapped images while retaining the missing alt text warning', function () {
    $mediaAsset = MediaAsset::factory()->create(['alt_text' => null]);
    writeWordPressManifest($this->manifestPath, [
        'source' => ['media_endpoint' => 'https://legacy.example/wp-json/wp/v2/media'],
        'media' => [['wordpress_id' => 49925, 'decision' => 'import']],
        'mappings' => [
            'media' => [
                '49925' => [
                    'media_asset_id' => $mediaAsset->id,
                    'alt_text' => null,
                    'mime_type' => 'image/png',
                ],
            ],
        ],
    ]);

    $this->pendingArtisan('wordpress:import', [
        'phase' => 'media',
        '--manifest' => $this->manifestPath,
        '--dry-run' => true,
    ])
        ->expectsOutputToContain('hergebruikt')
        ->expectsOutputToContain('Ontbrekende alt-tekst: 1')
        ->assertSuccessful();

    Http::assertNothingSent();
});

/**
 * @return array<string, mixed>
 */
function wordpressMediaRecord(
    int $id,
    string $altText = 'Een FPV-racedrone op de baan',
    string $caption = '',
    string $sourceUrl = 'https://legacy.example/wp-content/uploads/2025/09/race-cover.png',
    string $filename = '2025/09/race-cover.png',
    string $mimeType = 'image/png',
): array {
    return [
        'id' => $id,
        'source_url' => $sourceUrl,
        'mime_type' => $mimeType,
        'alt_text' => $altText,
        'caption' => ['rendered' => $caption],
        'media_details' => [
            'file' => $filename,
            'width' => 1,
            'height' => 1,
        ],
    ];
}

function onePixelPng(): string
{
    return (string) base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wl2nkwAAAAASUVORK5CYII=',
        strict: true,
    );
}

/** @param array<string, mixed> $manifest */
function writeWordPressManifest(string $manifestPath, array $manifest): void
{
    File::put(
        $manifestPath,
        json_encode($manifest, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
    );
}
