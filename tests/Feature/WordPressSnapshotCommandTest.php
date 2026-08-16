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
