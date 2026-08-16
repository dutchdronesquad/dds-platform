<?php

use App\Models\Location;
use App\Models\MediaAsset;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

beforeEach(function () {
    Http::preventStrayRequests();
    $identifier = Str::uuid();
    $this->directory = storage_path("app/wordpress-snapshot-{$identifier}");
    $this->manifestPath = storage_path("app/wordpress-snapshot-manifest-{$identifier}.json");
    $this->xmlPath = storage_path("app/wordpress-snapshot-export-{$identifier}.xml");
    $this->reportPath = storage_path("app/wordpress-snapshot-pages-{$identifier}.md");
});

afterEach(function () {
    File::delete([$this->manifestPath, $this->xmlPath, $this->reportPath]);
    File::deleteDirectory($this->directory);
});

test('it creates a verified source bundle that all importer sources can use offline', function () {
    $author = User::factory()->create();
    $coverImage = MediaAsset::factory()->create();
    $location = Location::factory()->create(['slug' => 'sportpaleis-alkmaar']);

    writeWordPressSnapshotManifest($this->manifestPath, [
        'source' => [
            'posts_endpoint' => 'https://legacy.example/wp-json/wp/v2/posts',
            'pages_endpoint' => 'https://legacy.example/wp-json/wp/v2/pages',
            'media_endpoint' => 'https://legacy.example/wp-json/wp/v2/media',
        ],
        'defaults' => ['author_id' => $author->id],
        'media' => [
            ['wordpress_id' => 49925, 'decision' => 'import'],
            ['wordpress_id' => 49926, 'decision' => 'skip', 'reason' => 'Templateafbeelding.'],
        ],
        'posts' => [
            [
                'wordpress_id' => 49916,
                'slug' => 'seizoen-25-26',
                'title' => 'Indoor seizoen 25/26',
                'published_at' => '2025-09-18T10:15:00Z',
                'category' => 'announcement',
                'decision' => 'import',
            ],
            [
                'wordpress_id' => 49917,
                'slug' => 'templatebericht',
                'title' => 'Templatebericht',
                'published_at' => '2025-09-18T10:15:00Z',
                'category' => 'news',
                'decision' => 'skip',
                'reason' => 'Niet overzetten.',
            ],
        ],
        'pages' => [[
            'wordpress_id' => 49498,
            'slug' => 'sportpaleis',
            'title' => 'Sportpaleis',
            'decision' => 'rewrite',
            'target' => [
                'type' => 'location',
                'location_slug' => $location->slug,
            ],
        ]],
        'mappings' => [
            'media' => [
                '49925' => ['media_asset_id' => $coverImage->id],
            ],
        ],
    ]);
    File::put($this->xmlPath, wordpressSnapshotXml());

    Http::fake([
        'legacy.example/wp-json/wp/v2/posts*' => Http::response([
            wordpressSnapshotPostRecord(),
            [...wordpressSnapshotPostRecord(), 'id' => 49917],
        ], headers: ['X-WP-TotalPages' => '1', 'X-WP-Total' => '2']),
        'legacy.example/wp-json/wp/v2/pages*' => Http::response([
            wordpressSnapshotPageRecord(),
        ], headers: ['X-WP-TotalPages' => '1', 'X-WP-Total' => '1']),
        'legacy.example/wp-json/wp/v2/media*' => Http::response([
            wordpressSnapshotMediaRecord(),
            wordpressSnapshotMediaRecord(
                id: 49926,
                sourceUrl: 'https://legacy.example/wp-content/uploads/gallery-cover.png',
                filename: 'gallery-cover.png',
            ),
        ], headers: ['X-WP-TotalPages' => '1', 'X-WP-Total' => '2']),
        'legacy.example/wp-content/uploads/race-cover.png' => Http::response(
            wordpressSnapshotPng(),
            headers: ['Content-Type' => 'image/png'],
        ),
        'legacy.example/wp-content/uploads/gallery-cover.png' => Http::response(
            wordpressSnapshotPng(),
            headers: ['Content-Type' => 'image/png'],
        ),
    ]);

    $this->pendingArtisan('wordpress:snapshot', [
        '--manifest' => $this->manifestPath,
        '--xml' => $this->xmlPath,
        '--output' => $this->directory,
    ])
        ->expectsOutputToContain('Zelfstandige WordPress bronbundel is compleet')
        ->assertSuccessful();

    $manifest = json_decode(File::get($this->manifestPath), true, flags: JSON_THROW_ON_ERROR);
    $snapshot = json_decode(
        File::get($this->directory.'/snapshot.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect(data_get($manifest, 'source.snapshot_directory'))
        ->toBe('storage/app/'.basename($this->directory))
        ->and(File::exists($this->directory.'/wordpress-export.xml'))->toBeTrue()
        ->and(File::exists($this->directory.'/posts.json'))->toBeTrue()
        ->and(File::exists($this->directory.'/pages.json'))->toBeTrue()
        ->and(File::exists($this->directory.'/media.json'))->toBeTrue()
        ->and(File::exists($this->directory.'/media/49925-race-cover.png'))->toBeTrue()
        ->and(File::exists($this->directory.'/media/49926-gallery-cover.png'))->toBeFalse()
        ->and(data_get($snapshot, 'collections.posts.count'))->toBe(1)
        ->and(data_get($snapshot, 'collections.posts.source_count'))->toBe(2)
        ->and(data_get($snapshot, 'collections.pages.count'))->toBe(1)
        ->and(data_get($snapshot, 'collections.media.count'))->toBe(1)
        ->and(data_get($snapshot, 'collections.media.source_count'))->toBe(2)
        ->and(data_get($snapshot, 'collections.media.source_reported_count'))->toBe(2)
        ->and(data_get($snapshot, 'media_files'))->not->toHaveKey('49926')
        ->and(data_get($snapshot, 'media_files.49925.checksum_sha256'))
        ->toBe(hash('sha256', wordpressSnapshotPng()));

    $requestsAfterSnapshot = 4;
    Http::assertSentCount($requestsAfterSnapshot);

    $this->pendingArtisan('wordpress:import', [
        'phase' => 'media',
        '--manifest' => $this->manifestPath,
        '--dry-run' => true,
    ])->assertSuccessful();

    $this->pendingArtisan('wordpress:import', [
        'phase' => 'posts',
        '--manifest' => $this->manifestPath,
        '--dry-run' => true,
    ])->assertSuccessful();

    $this->pendingArtisan('wordpress:import', [
        'phase' => 'pages',
        '--manifest' => $this->manifestPath,
        '--report' => $this->reportPath,
        '--dry-run' => true,
    ])->assertSuccessful();

    Http::assertSentCount($requestsAfterSnapshot);

    File::put($this->directory.'/posts.json', "[]\n");

    $this->pendingArtisan('wordpress:import', [
        'phase' => 'posts',
        '--manifest' => $this->manifestPath,
        '--dry-run' => true,
    ])
        ->expectsOutputToContain('Checksum van WordPress posts-inventaris klopt niet')
        ->assertFailed();

    Http::assertSentCount($requestsAfterSnapshot);
});

test('it refuses to overwrite an existing source bundle without force', function () {
    File::ensureDirectoryExists($this->directory);
    File::put($this->directory.'/keep.txt', 'retain');
    writeWordPressSnapshotManifest($this->manifestPath, [
        'source' => [
            'posts_endpoint' => 'https://legacy.example/wp-json/wp/v2/posts',
            'pages_endpoint' => 'https://legacy.example/wp-json/wp/v2/pages',
            'media_endpoint' => 'https://legacy.example/wp-json/wp/v2/media',
        ],
    ]);
    File::put($this->xmlPath, '<?xml version="1.0"?><rss />');

    $this->pendingArtisan('wordpress:snapshot', [
        '--manifest' => $this->manifestPath,
        '--xml' => $this->xmlPath,
        '--output' => $this->directory,
    ])
        ->expectsOutputToContain('Bronbundelmap bestaat al')
        ->assertFailed();

    expect(File::get($this->directory.'/keep.txt'))->toBe('retain');
    Http::assertNothingSent();
});

test('it rejects invalid snapshot inputs', function (Closure $prepare, string $message) {
    writeWordPressSnapshotManifest($this->manifestPath, validSnapshotManifest());
    File::put($this->xmlPath, '<?xml version="1.0"?><rss />');
    $prepare($this);

    $this->pendingArtisan('wordpress:snapshot', [
        '--manifest' => $this->manifestPath,
        '--xml' => $this->xmlPath,
        '--output' => $this->directory,
    ])
        ->expectsOutputToContain($message)
        ->assertFailed();

    expect(File::isDirectory($this->directory))->toBeFalse();
    Http::assertNothingSent();
})->with([
    'missing manifest' => [
        fn ($test) => File::delete($test->manifestPath),
        'Manifest niet gevonden:',
    ],
    'invalid manifest JSON' => [
        fn ($test) => File::put($test->manifestPath, '{'),
        'Syntax error',
    ],
    'non-object manifest' => [
        fn ($test) => File::put($test->manifestPath, 'null'),
        'JSON-object',
    ],
    'invalid endpoint' => [
        function ($test) {
            $manifest = validSnapshotManifest();
            $manifest['source']['pages_endpoint'] = 'ftp://legacy.example/pages';
            writeWordPressSnapshotManifest($test->manifestPath, $manifest);
        },
        'source.pages_endpoint',
    ],
    'empty output' => [
        fn ($test) => $test->directory = '',
        'Kies een specifieke map',
    ],
    'missing XML' => [
        fn ($test) => File::delete($test->xmlPath),
        'Geldige WordPress XML-export niet gevonden',
    ],
    'wrong XML extension' => [
        fn ($test) => $test->xmlPath = $test->reportPath,
        'Geldige WordPress XML-export niet gevonden',
    ],
]);

test('it rejects invalid snapshot selections', function (Closure $mutate, string $message) {
    $manifest = validSnapshotManifest();
    $mutate($manifest);
    writeWordPressSnapshotManifest($this->manifestPath, $manifest);
    File::put($this->xmlPath, '<?xml version="1.0"?><rss />');
    Http::fake([
        'legacy.example/wp-json/wp/v2/*' => Http::response([], headers: [
            'X-WP-TotalPages' => '1',
            'X-WP-Total' => '0',
        ]),
    ]);

    $this->pendingArtisan('wordpress:snapshot', [
        '--manifest' => $this->manifestPath,
        '--xml' => $this->xmlPath,
        '--output' => $this->directory,
    ])
        ->expectsOutputToContain($message)
        ->assertFailed();

    expect(File::isDirectory($this->directory))->toBeFalse();
})->with([
    'non-list posts' => [
        fn (array &$manifest) => $manifest['posts'] = ['selection' => []],
        'posts-lijst',
    ],
    'invalid post ID' => [
        fn (array &$manifest) => $manifest['posts'] = [['wordpress_id' => 0, 'decision' => 'import']],
        'geldige wordpress_id',
    ],
    'duplicate post ID' => [
        fn (array &$manifest) => $manifest['posts'] = [
            ['wordpress_id' => 12, 'decision' => 'import'],
            ['wordpress_id' => 12, 'decision' => 'skip'],
        ],
        'staat dubbel',
    ],
]);

test('it rejects malformed WordPress snapshot inventories', function (
    Closure $postsResponse,
    array $posts,
    string $message,
) {
    $manifest = validSnapshotManifest();
    $manifest['posts'] = $posts;
    writeWordPressSnapshotManifest($this->manifestPath, $manifest);
    File::put($this->xmlPath, '<?xml version="1.0"?><rss />');
    Http::fake(function ($request) use ($postsResponse) {
        if (str_contains($request->url(), '/posts')) {
            return $postsResponse();
        }

        return Http::response([], headers: ['X-WP-TotalPages' => '1', 'X-WP-Total' => '0']);
    });

    $this->pendingArtisan('wordpress:snapshot', [
        '--manifest' => $this->manifestPath,
        '--xml' => $this->xmlPath,
        '--output' => $this->directory,
    ])
        ->expectsOutputToContain($message)
        ->assertFailed();

    expect(File::isDirectory($this->directory))->toBeFalse();
})->with([
    'HTTP failure' => [
        fn () => Http::response([], 503),
        [],
        'mislukt met HTTP 503',
    ],
    'invalid response' => [
        fn () => Http::response(['record' => []]),
        [],
        'geen geldige posts-inventaris',
    ],
    'invalid record ID' => [
        fn () => Http::response([['id' => 0]], headers: ['X-WP-Total' => '1']),
        [],
        'ongeldige of dubbele posts-ID',
    ],
    'duplicate record ID' => [
        fn () => Http::response([['id' => 12], ['id' => 12]], headers: ['X-WP-Total' => '2']),
        [],
        'ongeldige of dubbele posts-ID',
    ],
    'reported total mismatch' => [
        fn () => Http::response([['id' => 12]], headers: ['X-WP-Total' => '2']),
        [],
        'rapporteert 2 posts-records maar leverde er 1',
    ],
    'missing selected record' => [
        fn () => Http::response([['id' => 12]], headers: ['X-WP-Total' => '1']),
        [['wordpress_id' => 13, 'decision' => 'import']],
        'Geselecteerd WordPress posts-record 13 ontbreekt',
    ],
]);

test('it reports snapshot connection failures and removes its temporary directory', function () {
    writeWordPressSnapshotManifest($this->manifestPath, validSnapshotManifest());
    File::put($this->xmlPath, '<?xml version="1.0"?><rss />');
    Http::fake(['legacy.example/wp-json/wp/v2/posts*' => Http::failedConnection('offline')]);

    $this->pendingArtisan('wordpress:snapshot', [
        '--manifest' => $this->manifestPath,
        '--xml' => $this->xmlPath,
        '--output' => $this->directory,
    ])
        ->expectsOutputToContain('WordPress REST-verbinding voor posts mislukt: offline')
        ->assertFailed();

    expect(File::glob($this->directory.'.building-*'))->toBeEmpty();
});

test('it replaces the exact snapshot directory when forced', function () {
    File::ensureDirectoryExists($this->directory);
    File::put($this->directory.'/obsolete.txt', 'remove');
    writeWordPressSnapshotManifest($this->manifestPath, validSnapshotManifest());
    File::put($this->xmlPath, '<?xml version="1.0"?><rss />');
    Http::fake([
        'legacy.example/wp-json/wp/v2/*' => Http::response([], headers: [
            'X-WP-TotalPages' => '1',
            'X-WP-Total' => '0',
        ]),
    ]);

    $this->pendingArtisan('wordpress:snapshot', [
        '--manifest' => $this->manifestPath,
        '--xml' => $this->xmlPath,
        '--output' => $this->directory,
        '--force' => true,
    ])->assertSuccessful();

    expect(File::exists($this->directory.'/obsolete.txt'))->toBeFalse()
        ->and(File::exists($this->directory.'/snapshot.json'))->toBeTrue();
});

test('it rejects media larger than the configured import limit while building the snapshot', function () {
    config()->set('media-library.max_file_size', 1);
    $manifest = validSnapshotManifest();
    $manifest['media'] = [['wordpress_id' => 49925, 'decision' => 'import']];
    writeWordPressSnapshotManifest($this->manifestPath, $manifest);
    File::put($this->xmlPath, '<?xml version="1.0"?><rss />');
    Http::fake([
        'legacy.example/wp-json/wp/v2/posts*' => Http::response([], headers: [
            'X-WP-TotalPages' => '1',
            'X-WP-Total' => '0',
        ]),
        'legacy.example/wp-json/wp/v2/pages*' => Http::response([], headers: [
            'X-WP-TotalPages' => '1',
            'X-WP-Total' => '0',
        ]),
        'legacy.example/wp-json/wp/v2/media*' => Http::response([
            wordpressSnapshotMediaRecord(),
        ], headers: [
            'X-WP-TotalPages' => '1',
            'X-WP-Total' => '1',
        ]),
        'legacy.example/wp-content/uploads/race-cover.png' => Http::response(wordpressSnapshotPng()),
    ]);

    $this->pendingArtisan('wordpress:snapshot', [
        '--manifest' => $this->manifestPath,
        '--xml' => $this->xmlPath,
        '--output' => $this->directory,
    ])
        ->expectsOutputToContain('Media 49925 is groter dan de toegestane 1 bytes.')
        ->assertFailed();

    expect(File::isDirectory($this->directory))->toBeFalse()
        ->and(File::glob($this->directory.'.building-*'))->toBeEmpty();
});

/** @return array<string, mixed> */
function validSnapshotManifest(): array
{
    return [
        'source' => [
            'posts_endpoint' => 'https://legacy.example/wp-json/wp/v2/posts',
            'pages_endpoint' => 'https://legacy.example/wp-json/wp/v2/pages',
            'media_endpoint' => 'https://legacy.example/wp-json/wp/v2/media',
        ],
        'posts' => [],
        'pages' => [],
        'media' => [],
    ];
}

/** @return array<string, mixed> */
function wordpressSnapshotPostRecord(): array
{
    return [
        'id' => 49916,
        'status' => 'publish',
        'link' => 'https://legacy.example/seizoen-25-26/',
        'slug' => 'seizoen-25-26',
        'content' => ['rendered' => '<p>Nieuw seizoen.</p>'],
        'author' => 7,
        'featured_media' => 49925,
        'categories' => [4],
        'tags' => [],
    ];
}

/** @return array<string, mixed> */
function wordpressSnapshotPageRecord(): array
{
    return [
        'id' => 49498,
        'status' => 'publish',
        'link' => 'https://legacy.example/sportpaleis/',
        'slug' => 'sportpaleis',
        'title' => ['rendered' => 'Sportpaleis'],
        'featured_media' => 49925,
        'content' => ['rendered' => '<p>Indoorlocatie.</p>'],
    ];
}

/** @return array<string, mixed> */
function wordpressSnapshotMediaRecord(
    int $id = 49925,
    string $sourceUrl = 'https://legacy.example/wp-content/uploads/race-cover.png',
    string $filename = '2025/09/race-cover.png',
): array {
    return [
        'id' => $id,
        'source_url' => $sourceUrl,
        'mime_type' => 'image/png',
        'alt_text' => 'Racecover',
        'caption' => ['rendered' => ''],
        'media_details' => [
            'file' => $filename,
            'width' => 1,
            'height' => 1,
        ],
    ];
}

function wordpressSnapshotPng(): string
{
    return (string) base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wl2nkwAAAAASUVORK5CYII=',
        strict: true,
    );
}

function wordpressSnapshotXml(): string
{
    return <<<'XML'
<?xml version="1.0" encoding="UTF-8" ?>
<rss xmlns:wp="http://wordpress.org/export/1.2/">
    <channel>
        <item>
            <title>Gallery cover</title>
            <wp:post_id>49926</wp:post_id>
            <wp:post_type>attachment</wp:post_type>
            <wp:status>inherit</wp:status>
            <wp:attachment_url>https://legacy.example/wp-content/uploads/gallery-cover.png</wp:attachment_url>
        </item>
    </channel>
</rss>
XML;
}

/** @param array<string, mixed> $manifest */
function writeWordPressSnapshotManifest(string $path, array $manifest): void
{
    File::put(
        $path,
        json_encode($manifest, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
    );
}
