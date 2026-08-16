<?php

use App\Models\Article;
use App\Models\Location;
use App\Models\MediaAsset;
use App\Models\Redirect;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

beforeEach(function () {
    Http::preventStrayRequests();
    $this->rehearsalDirectory = storage_path('app/wordpress-rehearsal-'.Str::uuid());
    $this->manifestPath = $this->rehearsalDirectory.'/selection.json';
    $this->reportPath = $this->rehearsalDirectory.'/rehearsal-review.md';
    File::ensureDirectoryExists($this->rehearsalDirectory);
});

afterEach(function () {
    File::deleteDirectory($this->rehearsalDirectory);
});

test('it runs the full import twice and records idempotency and staging smoke evidence', function () {
    [$article, $mediaAsset, $location] = writeReadyRehearsalManifest($this->manifestPath);
    fakeReadyRehearsalResponses($article, $location);

    $this->pendingArtisan('wordpress:rehearse', [
        '--manifest' => $this->manifestPath,
        '--base-url' => 'https://staging.example',
        '--report' => $this->reportPath,
        '--approve-manual-review' => true,
    ])
        ->expectsOutputToContain('Rehearsalstatus: READY')
        ->expectsOutputToContain('Publieke samples: 7 | Geslaagd: 7')
        ->assertSuccessful();

    $manifest = json_decode(File::get($this->manifestPath), true, flags: JSON_THROW_ON_ERROR);
    $rehearsal = data_get($manifest, 'rehearsal');

    expect($rehearsal)->toMatchArray([
        'status' => 'ready',
        'base_url' => 'https://staging.example',
        'manual_review_approved' => true,
        'blockers' => [],
    ])
        ->and(data_get($rehearsal, 'second_pass.media.imported'))->toBe(0)
        ->and(data_get($rehearsal, 'second_pass.posts.imported'))->toBe(0)
        ->and(data_get($rehearsal, 'second_pass.pages.imported'))->toBe(0)
        ->and(data_get($rehearsal, 'second_pass.cleanup.imported'))->toBe(0)
        ->and(data_get($rehearsal, 'second_pass.redirects.imported'))->toBe(0)
        ->and(data_get($rehearsal, 'persistent_counts.after_first_pass'))
        ->toBe(data_get($rehearsal, 'persistent_counts.after_second_pass'))
        ->and(Article::query()->count())->toBe(1)
        ->and(MediaAsset::query()->count())->toBe(1)
        ->and(Redirect::query()->count())->toBe(3)
        ->and(File::get($this->reportPath))->toContain(
            '# WordPress Staging Rehearsal',
            'Rehearsal status: **READY**',
            'Two-pass import evidence',
            'Persistent record counts',
            'Public sample checks',
            'Concrete blockers',
            '- None.',
        )
        ->and(File::get($this->rehearsalDirectory.'/import-review.md'))
        ->toContain('Launch review status: **READY**');
});

test('it blocks completion until visual public and admin review is explicitly approved', function () {
    [$article, , $location] = writeReadyRehearsalManifest($this->manifestPath);
    fakeReadyRehearsalResponses($article, $location);

    $this->pendingArtisan('wordpress:rehearse', [
        '--manifest' => $this->manifestPath,
        '--base-url' => 'https://staging.example',
        '--report' => $this->reportPath,
    ])
        ->expectsOutputToContain('Rehearsalstatus: BLOCKED')
        ->expectsOutputToContain('Visuele publieke samples en de beheerreview zijn nog niet handmatig goedgekeurd')
        ->assertFailed();

    $manifest = json_decode(File::get($this->manifestPath), true, flags: JSON_THROW_ON_ERROR);

    expect(data_get($manifest, 'rehearsal.status'))->toBe('blocked')
        ->and(data_get($manifest, 'rehearsal.blockers.0.id'))->toBe('DDS-022-B001')
        ->and(File::get($this->reportPath))->toContain(
            'Rehearsal status: **BLOCKED**',
            'DDS-022-B001',
            'Manual visual/admin review: pending',
        );
});

test('it fails safely when the staging manifest is unavailable', function () {
    File::delete($this->manifestPath);

    $this->pendingArtisan('wordpress:rehearse', [
        '--manifest' => $this->manifestPath,
        '--base-url' => 'https://staging.example',
    ])
        ->expectsOutputToContain('Stagingmanifest niet gevonden')
        ->assertFailed();

    Http::assertNothingSent();
});

/** @return array{Article, MediaAsset, Location} */
function writeReadyRehearsalManifest(string $manifestPath): array
{
    $article = Article::factory()->published()->create([
        'slug' => 'indoor-seizoen',
        'content' => 'Opgeschoonde inhoud.',
    ]);
    $mediaAsset = MediaAsset::factory()->create(['alt_text' => ['nl' => 'Race drone']]);
    $location = Location::factory()->create(['slug' => 'sportpaleis-alkmaar']);
    $postSourceUrl = 'https://legacy.example/2025/indoor-seizoen/';
    $locationSourceUrl = 'https://legacy.example/sportpaleis/';
    $aboutSourceUrl = 'https://legacy.example/about-us/';
    $locationTarget = '/locations/sportpaleis-alkmaar';
    $redirects = [
        [$postSourceUrl, '/news/indoor-seizoen'],
        [$locationSourceUrl, $locationTarget],
        [$aboutSourceUrl, '/about'],
    ];
    $redirectMappings = [];

    foreach ($redirects as [$sourceUrl, $targetUrl]) {
        $sourcePath = (string) parse_url($sourceUrl, PHP_URL_PATH);
        $redirect = Redirect::factory()->create([
            'source_path' => $sourcePath,
            'target_url' => $targetUrl,
        ]);
        $redirectMappings[hash('sha256', $redirect->source_path)] = [
            'redirect_id' => $redirect->id,
            'source_path' => $redirect->source_path,
            'target_url' => $redirect->target_url,
            'status_code' => 301,
            'review' => ['status' => 'not_required', 'notes' => null],
        ];
    }

    $locationContent = '<p>Indoor locatie.</p>';
    $aboutContent = '<p>Over DDS.</p>';
    $manifest = [
        'source' => [
            'media_endpoint' => 'https://legacy.example/wp-json/wp/v2/media',
            'posts_endpoint' => 'https://legacy.example/wp-json/wp/v2/posts',
            'pages_endpoint' => 'https://legacy.example/wp-json/wp/v2/pages',
        ],
        'media' => [[
            'wordpress_id' => 49925,
            'decision' => 'import',
        ]],
        'posts' => [[
            'wordpress_id' => 49916,
            'slug' => 'indoor-seizoen',
            'title' => 'Indoor seizoen',
            'published_at' => '2025-09-18T10:15:00Z',
            'category' => 'announcement',
            'decision' => 'import',
        ]],
        'pages' => [
            [
                'wordpress_id' => 49498,
                'slug' => 'sportpaleis',
                'title' => 'Sportpaleis',
                'decision' => 'rewrite',
                'target' => ['type' => 'location', 'location_slug' => $location->slug],
            ],
            [
                'wordpress_id' => 316,
                'slug' => 'about-us',
                'title' => 'About Us',
                'decision' => 'redirect',
                'target' => ['type' => 'route', 'route_name' => 'about'],
            ],
        ],
        'redirects' => [],
        'mappings' => [
            'media' => [
                '49925' => [
                    'media_asset_id' => $mediaAsset->id,
                    'source_url' => 'https://legacy.example/uploads/race-drone.jpg',
                    'mime_type' => 'image/jpeg',
                    'alt_text' => 'Race drone',
                ],
            ],
            'posts' => [
                '49916' => [
                    'article_id' => $article->id,
                    'source_url' => $postSourceUrl,
                    'content_checksum_sha256' => hash('sha256', '<p>Broninhoud.</p>'),
                    'cleanup' => [
                        'format' => 'plain_text',
                        'source_checksum_sha256' => hash('sha256', '<p>Broninhoud.</p>'),
                        'output_checksum_sha256' => hash('sha256', $article->content),
                        'unresolved_links' => [],
                        'missing_media' => [],
                        'suspicious_markup' => [],
                        'transformations' => [],
                    ],
                ],
            ],
            'pages' => [
                '49498' => [
                    'source_url' => $locationSourceUrl,
                    'source_slug' => 'sportpaleis',
                    'decision' => 'rewrite',
                    'target' => [
                        'type' => 'location',
                        'location_id' => $location->id,
                        'location_slug' => $location->slug,
                        'path' => $locationTarget,
                    ],
                    'content_checksum_sha256' => hash('sha256', $locationContent),
                    'review' => ['status' => 'approved', 'notes' => 'Stagingreview gereed.', 'source_changed' => false],
                ],
                '316' => [
                    'source_url' => $aboutSourceUrl,
                    'source_slug' => 'about-us',
                    'decision' => 'redirect',
                    'target' => ['type' => 'route', 'route_name' => 'about', 'path' => '/about'],
                    'content_checksum_sha256' => hash('sha256', $aboutContent),
                    'review' => ['status' => 'not_required', 'notes' => null, 'source_changed' => false],
                ],
            ],
            'redirects' => $redirectMappings,
        ],
    ];

    File::put(
        $manifestPath,
        json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
    );

    return [$article, $mediaAsset, $location];
}

function fakeReadyRehearsalResponses(Article $article, Location $location): void
{
    Http::fake(function (Request $request) use ($article, $location) {
        if (str_starts_with($request->url(), 'https://legacy.example/wp-json/wp/v2/pages')) {
            return Http::response([
                rehearsalPageRecord(49498, 'sportpaleis', 'Sportpaleis', 'https://legacy.example/sportpaleis/', '<p>Indoor locatie.</p>'),
                rehearsalPageRecord(316, 'about-us', 'About Us', 'https://legacy.example/about-us/', '<p>Over DDS.</p>'),
            ]);
        }

        $path = (string) parse_url($request->url(), PHP_URL_PATH);
        $redirectTargets = [
            '/2025/indoor-seizoen' => '/news/'.$article->slug,
            '/sportpaleis' => '/locations/'.$location->slug,
            '/about-us' => '/about',
        ];

        if (isset($redirectTargets[$path])) {
            return Http::response('', 301, ['Location' => $redirectTargets[$path]]);
        }

        return Http::response('<html><body>Staging sample</body></html>');
    });
}

/** @return array<string, mixed> */
function rehearsalPageRecord(
    int $id,
    string $slug,
    string $title,
    string $link,
    string $content,
): array {
    return [
        'id' => $id,
        'status' => 'publish',
        'link' => $link,
        'slug' => $slug,
        'title' => ['rendered' => $title],
        'featured_media' => 0,
        'content' => ['rendered' => $content],
    ];
}
