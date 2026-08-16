<?php

use App\Models\Article;
use App\Models\MediaAsset;
use App\Models\Redirect;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

beforeEach(function () {
    $identifier = Str::uuid();
    $this->manifestPath = storage_path("app/wordpress-import-report-{$identifier}.json");
    $this->reportPath = storage_path("app/wordpress-import-review-{$identifier}.md");
});

afterEach(function () {
    File::delete([$this->manifestPath, $this->reportPath]);
});

test('it generates a launch-ready report with phase totals and traceable mappings', function () {
    $article = Article::factory()->create(['slug' => 'indoor-seizoen']);
    $mediaAsset = MediaAsset::factory()->create();
    $redirect = Redirect::factory()->create([
        'source_path' => '/oud-bericht',
        'target_url' => '/news/indoor-seizoen',
    ]);
    $manifest = completeWordPressReviewManifest($article, $mediaAsset, $redirect);
    writeWordPressReviewManifest($this->manifestPath, $manifest);

    $this->pendingArtisan('wordpress:import', [
        'phase' => 'report',
        '--manifest' => $this->manifestPath,
        '--report' => $this->reportPath,
    ])
        ->expectsOutputToContain('Geselecteerd: 5 | Klaar: 0 | Geïmporteerd: 5 | Hergebruikt: 0 | Overgeslagen: 0 | Mislukt: 0')
        ->expectsOutputToContain('Reviewrapport bijgewerkt')
        ->assertSuccessful();

    expect(File::get($this->reportPath))->toContain(
        '# WordPress Staging Import Review',
        'Launch review status: **READY**',
        'Geselecteerd: 5 | Klaar: 0 | Geïmporteerd: 5 | Hergebruikt: 0 | Overgeslagen: 0 | Mislukt: 0',
        'https://legacy.example/indoor-seizoen/',
        "Article #{$article->id} (/news/indoor-seizoen)",
        "MediaAsset #{$mediaAsset->id}",
        '/oud-bericht',
        'Artifact removal policy',
        'must never delete normalized Articles',
    );

    File::delete([$this->manifestPath, $this->reportPath]);

    $this->assertModelExists($article);
    $this->assertModelExists($mediaAsset);
    $this->assertModelExists($redirect);
});

test('it gives command output and the report the same blockers skips and review work', function () {
    $article = Article::factory()->create(['slug' => 'indoor-seizoen']);
    $mediaAsset = MediaAsset::factory()->create();
    $redirect = Redirect::factory()->inactive()->create([
        'source_path' => '/onzeker',
        'target_url' => '/events',
    ]);
    $manifest = completeWordPressReviewManifest($article, $mediaAsset, $redirect);
    data_set($manifest, 'mappings.media.49925.alt_text', null);
    data_set($manifest, 'mappings.posts.49916.cleanup.unresolved_links', ['https://legacy.example/onbekend/']);
    data_set($manifest, 'mappings.posts.49916.cleanup.missing_media', ['https://legacy.example/missing.jpg']);
    data_set($manifest, 'mappings.posts.49916.cleanup.suspicious_markup', ['Niet-ondersteunde iframe verwijderd.']);
    data_set($manifest, 'mappings.pages.316.review.status', 'pending');
    data_set($manifest, 'mappings.redirects.redirect-one.review.status', 'pending');
    data_set($manifest, 'runs.media.failed', 1);
    data_set($manifest, 'runs.media.items', [[
        'wordpress_id' => 49926,
        'status' => 'mislukt',
        'message' => 'Download gaf HTTP 404.',
    ]]);
    data_set($manifest, 'runs.posts.skipped', 1);
    data_set($manifest, 'runs.posts.items', [[
        'wordpress_id' => 1158,
        'status' => 'overgeslagen',
        'message' => 'Niet geselecteerd na inhoudsreview.',
    ]]);
    data_set($manifest, 'runs.redirects.conflicts', 1);
    data_set($manifest, 'runs.redirects.failed', 1);
    data_set($manifest, 'runs.redirects.items', [[
        'wordpress_id' => '/dubbel',
        'status' => 'conflict',
        'message' => 'Meerdere doelen voor hetzelfde bronpad.',
    ]]);
    writeWordPressReviewManifest($this->manifestPath, $manifest);

    $exitCode = Artisan::call('wordpress:import', [
        'phase' => 'report',
        '--manifest' => $this->manifestPath,
        '--report' => $this->reportPath,
    ]);
    $commandOutput = Artisan::output();
    $reportContents = File::get($this->reportPath);

    expect($exitCode)->toBe(1)
        ->and($commandOutput)->toContain(
            'media:49926',
            'Download gaf HTTP 404',
            'posts:1158',
            'Niet geselecteerd na inhoudsreview',
            'redirects:/dubbel',
            'Meerdere doelen voor hetzelfde bronpad',
        )
        ->and($reportContents)->toContain(
            'Launch review status: **BLOCKED**',
            'media:49926 [mislukt]: media: Download gaf HTTP 404.',
            'posts:1158 [overgeslagen]: posts: Niet geselecteerd na inhoudsreview.',
            'redirects:/dubbel [mislukt]: redirects: Meerdere doelen voor hetzelfde bronpad.',
            'Media 49925 mist herbruikbare alt-tekst.',
            'https://legacy.example/onbekend/',
            'https://legacy.example/missing.jpg',
            'Pagina 316 wacht op handmatige rewrite-review.',
            'Redirect /onzeker wacht op review',
        );

    $commandSummary = Str::match('/Geselecteerd: .* Mislukt: \d+/', $commandOutput);
    $reportSummary = Str::match('/Geselecteerd: .* Mislukt: \d+/', $reportContents);

    expect($commandSummary)->not->toBeEmpty()->toBe($reportSummary);
});

test('it previews the consolidated report without writing it', function () {
    $article = Article::factory()->create(['slug' => 'indoor-seizoen']);
    $mediaAsset = MediaAsset::factory()->create();
    $redirect = Redirect::factory()->create();
    writeWordPressReviewManifest(
        $this->manifestPath,
        completeWordPressReviewManifest($article, $mediaAsset, $redirect),
    );

    $this->pendingArtisan('wordpress:import', [
        'phase' => 'report',
        '--manifest' => $this->manifestPath,
        '--report' => $this->reportPath,
        '--dry-run' => true,
    ])
        ->expectsOutputToContain('Dry-run')
        ->assertSuccessful();

    expect(File::exists($this->reportPath))->toBeFalse();
});

test('approved cleanup diagnostics remain visible without blocking launch', function () {
    $article = Article::factory()->create(['slug' => 'indoor-seizoen']);
    $mediaAsset = MediaAsset::factory()->create();
    $redirect = Redirect::factory()->create();
    $manifest = completeWordPressReviewManifest($article, $mediaAsset, $redirect);
    data_set($manifest, 'mappings.posts.49916.cleanup.suspicious_markup', [
        'Niet-ondersteunde iframe verwijderd.',
    ]);
    data_set($manifest, 'mappings.posts.49916.cleanup.review', [
        'status' => 'approved',
        'notes' => 'De oude embed is niet nodig in het gemigreerde artikel.',
    ]);
    writeWordPressReviewManifest($this->manifestPath, $manifest);

    $this->pendingArtisan('wordpress:import', [
        'phase' => 'report',
        '--manifest' => $this->manifestPath,
        '--report' => $this->reportPath,
    ])->assertSuccessful();

    expect(File::get($this->reportPath))->toContain(
        'Launch review status: **READY**',
        'Niet-ondersteunde iframe verwijderd.',
        '| 49916 | https://legacy.example/indoor-seizoen/ | '.
            "Article #{$article->id} (/news/indoor-seizoen) | approved |",
    );
});

test('every completed import phase records its latest outcome in the manifest', function () {
    writeWordPressReviewManifest($this->manifestPath, [
        'mappings' => ['posts' => [], 'pages' => []],
        'redirects' => [],
    ]);

    $this->pendingArtisan('wordpress:import', [
        'phase' => 'redirects',
        '--manifest' => $this->manifestPath,
        '--report' => $this->reportPath,
    ])->assertSuccessful();

    $manifest = json_decode(File::get($this->manifestPath), true, flags: JSON_THROW_ON_ERROR);

    expect(data_get($manifest, 'runs.redirects'))->toMatchArray([
        'selected' => 0,
        'imported' => 0,
        'reused' => 0,
        'skipped' => 0,
        'failed' => 0,
        'items' => [],
    ])
        ->and(data_get($manifest, 'runs.redirects.completed_at'))->toBeString();
});

/** @return array<string, mixed> */
function completeWordPressReviewManifest(
    Article $article,
    MediaAsset $mediaAsset,
    Redirect $redirect,
): array {
    $completedAt = '2026-08-04T12:00:00+02:00';
    $run = [
        'selected' => 1,
        'ready' => 0,
        'imported' => 1,
        'reused' => 0,
        'skipped' => 0,
        'failed' => 0,
        'items' => [],
        'completed_at' => $completedAt,
    ];

    return [
        'mappings' => [
            'media' => [
                '49925' => [
                    'media_asset_id' => $mediaAsset->id,
                    'source_url' => 'https://legacy.example/uploads/drone.jpg',
                    'mime_type' => 'image/jpeg',
                    'alt_text' => ['nl' => 'Race drone'],
                ],
            ],
            'posts' => [
                '49916' => [
                    'article_id' => $article->id,
                    'source_url' => 'https://legacy.example/indoor-seizoen/',
                    'cleanup' => [
                        'format' => 'plain_text',
                        'unresolved_links' => [],
                        'missing_media' => [],
                        'suspicious_markup' => [],
                    ],
                ],
            ],
            'pages' => [
                '316' => [
                    'source_url' => 'https://legacy.example/about-us/',
                    'decision' => 'redirect',
                    'target' => ['type' => 'route', 'path' => '/about'],
                    'review' => ['status' => 'not_required'],
                ],
            ],
            'redirects' => [
                'redirect-one' => [
                    'redirect_id' => $redirect->id,
                    'source_path' => $redirect->source_path,
                    'target_url' => $redirect->target_url,
                    'status_code' => 301,
                    'review' => ['status' => 'approved'],
                ],
            ],
        ],
        'runs' => [
            'media' => $run,
            'posts' => $run,
            'pages' => $run,
            'cleanup' => $run,
            'redirects' => $run,
        ],
    ];
}

/** @param array<string, mixed> $manifest */
function writeWordPressReviewManifest(string $manifestPath, array $manifest): void
{
    File::put(
        $manifestPath,
        json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
    );
}
