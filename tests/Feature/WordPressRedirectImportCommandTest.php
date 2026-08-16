<?php

use App\Models\Article;
use App\Models\Redirect;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

beforeEach(function () {
    $identifier = Str::uuid();
    $this->manifestPath = storage_path("app/wordpress-redirects-{$identifier}.json");
    $this->reportPath = storage_path("app/wordpress-redirect-review-{$identifier}.md");
});

afterEach(function () {
    File::delete([$this->manifestPath, $this->reportPath]);
});

test('it previews mapped and inventoried redirects without writing artifacts', function () {
    $article = Article::factory()->create(['slug' => 'indoor-seizoen']);
    writeWordPressRedirectManifest($this->manifestPath, wordpressRedirectManifest($article));

    $exitCode = Artisan::call('wordpress:import', [
        'phase' => 'redirects',
        '--manifest' => $this->manifestPath,
        '--report' => $this->reportPath,
        '--dry-run' => true,
    ]);

    expect($exitCode)->toBe(0)
        ->and(Artisan::output())->toContain(
            'Dry-run',
            'post:49916',
            '/news/indoor-seizoen',
            'Bronpad is al het canonieke doel',
            'Redirects in afwachting van review: 1',
        )
        ->and(Redirect::query()->count())->toBe(0)
        ->and(json_decode(File::get($this->manifestPath), true))->not->toHaveKey('mappings.redirects')
        ->and(File::exists($this->reportPath))->toBeFalse();
});

test('it imports common legacy redirects reuses them and exposes pending review', function () {
    $article = Article::factory()->create(['slug' => 'indoor-seizoen']);
    $existingTrainingRedirect = Redirect::factory()->create([
        'source_path' => '/trainingen',
        'target_url' => '/events?type=training',
        'notes' => 'Handmatig gecontroleerd.',
    ]);
    writeWordPressRedirectManifest($this->manifestPath, wordpressRedirectManifest($article));

    $this->pendingArtisan('wordpress:import', [
        'phase' => 'redirects',
        '--manifest' => $this->manifestPath,
        '--report' => $this->reportPath,
    ])
        ->expectsOutputToContain('hergebruikt')
        ->expectsOutputToContain('Redirects in afwachting van review: 1')
        ->assertSuccessful();

    $postRedirect = Redirect::query()->where('source_path', '/2025/indoor-seizoen')->sole();
    $aboutRedirect = Redirect::query()->where('source_path', '/about-us')->sole();
    $agendaRedirect = Redirect::query()->where('source_path', '/agenda')->sole();
    $manifest = json_decode(File::get($this->manifestPath), true, flags: JSON_THROW_ON_ERROR);

    expect($postRedirect)
        ->target_url->toBe('/news/indoor-seizoen')
        ->status_code->toBe(301)
        ->is_active->toBeTrue()
        ->and($aboutRedirect->target_url)->toBe('/about')
        ->and($agendaRedirect)
        ->target_url->toBe('/events')
        ->is_active->toBeFalse()
        ->and($agendaRedirect->notes)->toContain('review: pending', 'XML export; sitemap')
        ->and($existingTrainingRedirect->refresh()->notes)->toBe('Handmatig gecontroleerd.')
        ->and(data_get($manifest, 'mappings.redirects'))->toHaveCount(4)
        ->and(File::get($this->reportPath))->toContain(
            '# WordPress Redirect Import Review',
            '/2025/indoor-seizoen',
            '/news/indoor-seizoen',
            '/agenda',
            'pending',
        );

    $this->get('/2025/indoor-seizoen/')
        ->assertMovedPermanently()
        ->assertRedirect('/news/indoor-seizoen');

    $this->get('/about-us/')
        ->assertMovedPermanently()
        ->assertRedirect('/about');

    data_set($manifest, 'redirects.0.review.status', 'approved');
    writeWordPressRedirectManifest($this->manifestPath, $manifest);

    $this->pendingArtisan('wordpress:import', [
        'phase' => 'redirects',
        '--manifest' => $this->manifestPath,
        '--report' => $this->reportPath,
    ])
        ->expectsOutputToContain('Hergebruikt: 4')
        ->assertSuccessful();

    expect(Redirect::query()->count())->toBe(4)
        ->and($agendaRedirect->refresh()->is_active)->toBeTrue()
        ->and($agendaRedirect->notes)->toContain('review: approved');
});

test('it blocks duplicate and existing target conflicts while reporting safe skips', function () {
    $article = Article::factory()->create(['slug' => 'indoor-seizoen']);
    Redirect::factory()->create([
        'source_path' => '/bestaand',
        'target_url' => '/contact',
    ]);
    $manifest = wordpressRedirectManifest($article);
    $manifest['redirects'] = [
        [
            'source_path' => '/dubbel',
            'target_url' => '/news',
            'review' => ['status' => 'approved'],
        ],
        [
            'source_url' => 'https://legacy.example/dubbel/',
            'target_url' => '/about',
            'review' => ['status' => 'approved'],
        ],
        [
            'source_path' => '/bestaand',
            'target_url' => '/house-rules',
            'review' => ['status' => 'approved'],
        ],
        [
            'source_path' => '/niet-meenemen',
            'target_url' => '/news',
            'decision' => 'skip',
            'reason' => 'Niet aanwezig in de definitieve sitemap.',
        ],
    ];
    writeWordPressRedirectManifest($this->manifestPath, $manifest);

    $this->pendingArtisan('wordpress:import', [
        'phase' => 'redirects',
        '--manifest' => $this->manifestPath,
        '--report' => $this->reportPath,
    ])
        ->expectsOutputToContain('Redirectconflicten: 2')
        ->expectsOutputToContain('Niet aanwezig in de definitieve sitemap')
        ->assertFailed();

    expect(Redirect::query()->where('source_path', '/dubbel')->exists())->toBeFalse()
        ->and(Redirect::query()->where('source_path', '/bestaand')->sole()->target_url)->toBe('/contact')
        ->and(File::get($this->reportPath))->toContain(
            'Blocking conflicts',
            'Meerdere doelen voor hetzelfde bronpad',
            'Bestaande Redirect',
        );
});

/** @return array<string, mixed> */
function wordpressRedirectManifest(Article $article): array
{
    return [
        'mappings' => [
            'posts' => [
                '49916' => [
                    'article_id' => $article->id,
                    'source_url' => 'https://legacy.example/2025/indoor-seizoen/',
                ],
            ],
            'pages' => [
                '316' => [
                    'source_url' => 'https://legacy.example/about-us/',
                    'decision' => 'redirect',
                    'target' => ['type' => 'route', 'path' => '/about'],
                ],
                '49704' => [
                    'source_url' => 'https://legacy.example/trainingen/',
                    'decision' => 'rewrite',
                    'target' => ['type' => 'route', 'path' => '/events?type=training'],
                ],
                '319' => [
                    'source_url' => 'https://legacy.example/contact/',
                    'decision' => 'rewrite',
                    'target' => ['type' => 'route', 'path' => '/contact'],
                ],
                '318' => [
                    'source_url' => 'https://legacy.example/stories/',
                    'decision' => 'gone',
                    'target' => ['type' => 'gone', 'status_code' => 410],
                ],
            ],
        ],
        'redirects' => [
            [
                'source_path' => '/agenda/',
                'target_url' => '/events',
                'provenance' => 'XML export; sitemap',
                'review' => [
                    'status' => 'pending',
                    'notes' => 'Controleer of /kalender niet ook gebruikt werd.',
                ],
            ],
        ],
    ];
}

/** @param array<string, mixed> $manifest */
function writeWordPressRedirectManifest(string $manifestPath, array $manifest): void
{
    File::put(
        $manifestPath,
        json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
    );
}
