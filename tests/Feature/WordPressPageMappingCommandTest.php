<?php

use App\Models\Location;
use App\Models\Page;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

beforeEach(function () {
    Http::preventStrayRequests();
    $identifier = Str::uuid();
    $this->manifestPath = storage_path("app/wordpress-page-mapping-{$identifier}.json");
    $this->reportPath = storage_path("app/wordpress-page-review-{$identifier}.md");
});

afterEach(function () {
    File::delete([$this->manifestPath, $this->reportPath]);
});

test('it previews a complete deliberate page mapping without writing artifacts', function () {
    $location = Location::factory()->create(['slug' => 'sportpaleis-alkmaar']);
    $pages = wordpressPageRecords();

    writeWordPressPageManifest(
        $this->manifestPath,
        wordpressPageManifest($location->slug),
    );

    Http::fake([
        'legacy.example/wp-json/wp/v2/pages*' => Http::response($pages),
    ]);

    $exitCode = Artisan::call('wordpress:import', [
        'phase' => 'pages',
        '--manifest' => $this->manifestPath,
        '--report' => $this->reportPath,
        '--dry-run' => true,
    ]);

    expect($exitCode)->toBe(0)
        ->and(Artisan::output())
        ->toContain('Dry-run', 'Sportpaleis', 'klaar', 'About Us', 'Stories', '410')
        ->and(json_decode(File::get($this->manifestPath), true))->not->toHaveKey('mappings.pages')
        ->and(File::exists($this->reportPath))->toBeFalse();
});

test('it writes idempotent mappings and preserves manual review decisions', function () {
    $location = Location::factory()->create(['slug' => 'sportpaleis-alkmaar']);

    writeWordPressPageManifest(
        $this->manifestPath,
        wordpressPageManifest($location->slug),
    );

    Http::fake([
        'legacy.example/wp-json/wp/v2/pages*' => Http::response(wordpressPageRecords()),
    ]);

    $this->pendingArtisan('wordpress:import', [
        'phase' => 'pages',
        '--manifest' => $this->manifestPath,
        '--report' => $this->reportPath,
    ])
        ->expectsOutputToContain('Reviewrapport bijgewerkt')
        ->assertSuccessful();

    $manifest = json_decode(File::get($this->manifestPath), true, flags: JSON_THROW_ON_ERROR);

    expect(data_get($manifest, 'mappings.pages.49498.target'))->toMatchArray([
        'type' => 'location',
        'location_id' => $location->id,
        'location_slug' => 'sportpaleis-alkmaar',
        'path' => '/locations/sportpaleis-alkmaar',
    ])
        ->and(data_get($manifest, 'mappings.pages.316.target'))->toMatchArray([
            'type' => 'route',
            'route_name' => 'about',
            'path' => '/about',
        ])
        ->and(data_get($manifest, 'mappings.pages.49704.target'))->toMatchArray([
            'type' => 'route',
            'route_name' => 'events.index',
            'path' => '/events?type=training',
        ])
        ->and(data_get($manifest, 'mappings.pages.318.target'))->toBe([
            'type' => 'gone',
            'status_code' => 410,
        ])
        ->and(data_get($manifest, 'mappings.pages.49764.target'))->toBe([
            'type' => 'manual',
            'key' => 'media-overview',
            'path' => '/media',
        ])
        ->and(data_get($manifest, 'mappings.pages.49498.review.status'))->toBe('pending')
        ->and(File::get($this->reportPath))
        ->toContain('# WordPress Page Mapping Review', 'Manual rewrite work', 'media-overview');

    data_set($manifest, 'mappings.pages.49498.review', [
        'status' => 'approved',
        'notes' => 'Adres gecontroleerd; beschrijving moet nog korter.',
        'source_changed' => false,
    ]);
    writeWordPressPageManifest($this->manifestPath, $manifest);

    $this->pendingArtisan('wordpress:import', [
        'phase' => 'pages',
        '--manifest' => $this->manifestPath,
        '--report' => $this->reportPath,
    ])->assertSuccessful();

    $rerunManifest = json_decode(File::get($this->manifestPath), true, flags: JSON_THROW_ON_ERROR);

    expect(data_get($rerunManifest, 'mappings.pages.49498.review'))->toMatchArray([
        'status' => 'approved',
        'notes' => 'Adres gecontroleerd; beschrijving moet nog korter.',
        'source_changed' => false,
    ]);

    Http::assertSentCount(2);
});

test('it reports unmapped source pages and invalid targets instead of creating generic pages', function () {
    writeWordPressPageManifest($this->manifestPath, [
        'source' => [
            'pages_endpoint' => 'https://legacy.example/wp-json/wp/v2/pages',
        ],
        'pages' => [
            wordpressPageSelection(
                49498,
                'sportpaleis',
                'Sportpaleis',
                'rewrite',
                ['type' => 'location', 'location_slug' => 'ontbrekende-locatie'],
            ),
        ],
    ]);

    Http::fake([
        'legacy.example/wp-json/wp/v2/pages*' => Http::response([
            wordpressPageRecord(49498, 'sportpaleis', 'Sportpaleis'),
            wordpressPageRecord(49764, 'media', 'In de media'),
        ]),
    ]);

    $this->pendingArtisan('wordpress:import', [
        'phase' => 'pages',
        '--manifest' => $this->manifestPath,
        '--report' => $this->reportPath,
    ])
        ->expectsOutputToContain('Geen manifestbesluit')
        ->expectsOutputToContain('Doellocatie ontbrekende-locatie bestaat niet')
        ->assertFailed();

    expect(File::get($this->reportPath))
        ->toContain('Blocking failures', 'Geen manifestbesluit', 'ontbrekende-locatie')
        ->and(class_exists(Page::class))->toBeFalse();
});

/** @return array<string, mixed> */
function wordpressPageManifest(string $locationSlug): array
{
    return [
        'source' => [
            'pages_endpoint' => 'https://legacy.example/wp-json/wp/v2/pages',
        ],
        'pages' => [
            wordpressPageSelection(
                49498,
                'sportpaleis',
                'Sportpaleis',
                'rewrite',
                ['type' => 'location', 'location_slug' => $locationSlug],
            ),
            wordpressPageSelection(
                316,
                'about-us',
                'About Us',
                'redirect',
                ['type' => 'route', 'route_name' => 'about'],
            ),
            wordpressPageSelection(
                318,
                'stories',
                'Stories',
                'gone',
                reason: 'Ongebruikte templatepagina zonder vervangende bestemming.',
            ),
            wordpressPageSelection(
                49704,
                'trainingen',
                'Trainingsdagen',
                'rewrite',
                [
                    'type' => 'route',
                    'route_name' => 'events.index',
                    'query' => ['type' => 'training'],
                ],
            ),
            wordpressPageSelection(
                49764,
                'media',
                'In de media',
                'rewrite',
                ['type' => 'manual', 'key' => 'media-overview', 'path' => '/media'],
            ),
        ],
    ];
}

/** @return list<array<string, mixed>> */
function wordpressPageRecords(): array
{
    return [
        wordpressPageRecord(49764, 'media', 'In de media'),
        wordpressPageRecord(49498, 'sportpaleis', 'Sportpaleis'),
        wordpressPageRecord(49704, 'trainingen', 'Trainingsdagen'),
        wordpressPageRecord(318, 'stories', 'Stories'),
        wordpressPageRecord(316, 'about-us', 'About Us'),
    ];
}

/** @return array<string, mixed> */
function wordpressPageRecord(int $id, string $slug, string $title): array
{
    return [
        'id' => $id,
        'slug' => $slug,
        'status' => 'publish',
        'link' => "https://legacy.example/{$slug}/",
        'title' => ['rendered' => $title],
        'featured_media' => $id === 49498 ? 2046 : 0,
        'content' => ['rendered' => "<p>Broninhoud voor {$title}.</p>"],
    ];
}

/**
 * @param  array<string, mixed>|null  $target
 * @return array<string, mixed>
 */
function wordpressPageSelection(
    int $id,
    string $slug,
    string $title,
    string $decision,
    ?array $target = null,
    ?string $reason = null,
): array {
    return [
        'wordpress_id' => $id,
        'slug' => $slug,
        'title' => $title,
        'decision' => $decision,
        'target' => $target,
        'reason' => $reason,
    ];
}

/** @param array<string, mixed> $manifest */
function writeWordPressPageManifest(string $manifestPath, array $manifest): void
{
    File::put(
        $manifestPath,
        json_encode($manifest, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
    );
}
