<?php

use App\Models\Article;
use App\Models\MediaAsset;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

beforeEach(function () {
    $identifier = Str::uuid();
    $this->manifestPath = storage_path("app/wordpress-cleanup-{$identifier}.json");
    $this->reportPath = storage_path("app/wordpress-cleanup-review-{$identifier}.md");
});

afterEach(function () {
    File::delete([$this->manifestPath, $this->reportPath]);
});

test('it previews cleanup without changing imported content or artifacts', function () {
    $article = Article::factory()->create([
        'slug' => 'indoor-seizoen',
        'content' => '<h1>Indoor seizoen</h1><p>Bekijk <a href="https://legacy.example/onbekend/">de oude pagina</a>.</p>',
    ]);
    $originalContent = $article->content;
    writeWordPressCleanupManifest($this->manifestPath, wordpressCleanupManifest($article));

    $exitCode = Artisan::call('wordpress:import', [
        'phase' => 'cleanup',
        '--manifest' => $this->manifestPath,
        '--report' => $this->reportPath,
        '--dry-run' => true,
    ]);

    expect($exitCode)->toBe(0)
        ->and(Artisan::output())->toContain('Dry-run', 'klaar', 'Onopgeloste interne links: 1')
        ->and($article->refresh()->content)->toBe($originalContent)
        ->and(json_decode(File::get($this->manifestPath), true))->not->toHaveKey('mappings.posts.49916.cleanup')
        ->and(File::exists($this->reportPath))->toBeFalse();
});

test('it normalizes content rewrites known links and media and is idempotent', function () {
    $rawContent = <<<'HTML'
        [gallery ids="1,2"]
        <div class="elementor-section" style="color: red">
            <h1>Indoor seizoen</h1>
            <p>Lees <a href="https://legacy.example/2024/ander-bericht/">het vorige bericht</a> en bezoek <a href="/over-ons/">ons verhaal</a>.</p>
            <img src="https://legacy.example/wp-content/uploads/2025/drone-300x200.jpg" alt="Race drone">
            <iframe src="https://www.youtube.com/embed/abc123"></iframe>
            <div class="sharedaddy sd-sharing-enabled">Deel dit bericht</div>
        </div>
        HTML;
    $article = Article::factory()->create(['slug' => 'indoor-seizoen', 'content' => $rawContent]);
    $linkedArticle = Article::factory()->create(['slug' => 'ander-bericht']);
    $mediaAsset = MediaAsset::factory()->named('drone.jpg')->create();
    writeWordPressCleanupManifest(
        $this->manifestPath,
        wordpressCleanupManifest($article, $linkedArticle, $mediaAsset),
    );

    $this->pendingArtisan('wordpress:import', [
        'phase' => 'cleanup',
        '--manifest' => $this->manifestPath,
        '--report' => $this->reportPath,
    ])
        ->expectsOutputToContain('opgeschoond')
        ->expectsOutputToContain('Verdachte markup: 1')
        ->assertSuccessful();

    $content = $article->refresh()->content;
    $manifest = json_decode(File::get($this->manifestPath), true, flags: JSON_THROW_ON_ERROR);
    $cleanup = data_get($manifest, 'mappings.posts.49916.cleanup');

    expect($content)
        ->toContain('Indoor seizoen')
        ->toContain('het vorige bericht (/news/ander-bericht)')
        ->toContain('ons verhaal (/about)')
        ->toContain("![Race drone](<{$mediaAsset->url()}>)")
        ->toContain('Video: https://www.youtube.com/embed/abc123')
        ->not->toContain('<', '[gallery', 'elementor', 'Deel dit bericht')
        ->and($cleanup)->toMatchArray([
            'format' => 'markdown',
            'source_checksum_sha256' => hash('sha256', $rawContent),
            'output_checksum_sha256' => hash('sha256', $content),
            'unresolved_links' => [],
            'missing_media' => [],
        ])
        ->and($cleanup['suspicious_markup'])->toContain('Inline attribuut style op <div> verwijderd.')
        ->and(File::get($this->reportPath))->toContain(
            '# WordPress Content Cleanup Review',
            'Transformations',
            'WordPress-shortcode verwijderd',
            'YouTube-embed als link behouden',
        );

    $this->pendingArtisan('wordpress:import', [
        'phase' => 'cleanup',
        '--manifest' => $this->manifestPath,
        '--report' => $this->reportPath,
    ])
        ->expectsOutputToContain('hergebruikt')
        ->assertSuccessful();

    expect($article->refresh()->content)->toBe($content);
});

test('it upgrades unchanged plain text cleanup and uses the first imported image as a missing cover', function () {
    $rawContent = '<p>Een oud bericht.</p><img src="https://legacy.example/wp-content/uploads/2018/race.jpg" alt="FPV race">';
    $article = Article::factory()->create([
        'content' => 'Een oud bericht. Afbeelding: FPV race (https://media.example/race.jpg)',
        'cover_image_id' => null,
    ]);
    $mediaAsset = MediaAsset::factory()->named('race.jpg')->create();
    $manifest = wordpressCleanupManifest($article, mediaAsset: $mediaAsset);
    data_set($manifest, 'mappings.media.49925.source_url', 'https://legacy.example/wp-content/uploads/2018/race.jpg');
    data_set($manifest, 'mappings.posts.49916.content_checksum_sha256', hash('sha256', $rawContent));
    data_set($manifest, 'mappings.posts.49916.cleanup', [
        'format' => 'plain_text',
        'source_checksum_sha256' => hash('sha256', $rawContent),
        'output_checksum_sha256' => hash('sha256', $article->content),
        'unresolved_links' => [],
        'missing_media' => [],
        'suspicious_markup' => [],
        'transformations' => [],
    ]);
    writeWordPressCleanupManifest($this->manifestPath, $manifest);
    Http::fake([
        'https://legacy.example/wp-json/wp/v2/posts/49916' => Http::response([
            'id' => 49916,
            'content' => ['rendered' => $rawContent],
        ]),
    ]);

    $this->pendingArtisan('wordpress:import', [
        'phase' => 'cleanup',
        '--manifest' => $this->manifestPath,
        '--report' => $this->reportPath,
    ])
        ->expectsOutputToContain('opgeschoond')
        ->assertSuccessful();

    expect($article->refresh())
        ->content->toContain("![FPV race](<{$mediaAsset->url()}>)")
        ->cover_image_id->toBe($mediaAsset->id)
        ->and(data_get(json_decode(File::get($this->manifestPath), true), 'mappings.posts.49916.cleanup'))
        ->toMatchArray([
            'format' => 'markdown',
            'fallback_cover_media_asset_id' => $mediaAsset->id,
        ]);

    $this->pendingArtisan('wordpress:import', [
        'phase' => 'cleanup',
        '--manifest' => $this->manifestPath,
        '--report' => $this->reportPath,
    ])
        ->expectsOutputToContain('hergebruikt')
        ->assertSuccessful();
});

test('it reports unresolved media unsafe markup and protects manual edits', function () {
    $rawContent = '<p onclick="alert(1)"><img src="/wp-content/uploads/missing.jpg"><a href="javascript:alert(1)">Fout</a><iframe src="https://player.example/video"></iframe></p>';
    $article = Article::factory()->create(['content' => $rawContent]);
    writeWordPressCleanupManifest($this->manifestPath, wordpressCleanupManifest($article));

    $this->pendingArtisan('wordpress:import', [
        'phase' => 'cleanup',
        '--manifest' => $this->manifestPath,
        '--report' => $this->reportPath,
    ])
        ->expectsOutputToContain('Ontbrekende geïmporteerde media: 1')
        ->expectsOutputToContain('Verdachte markup: 3')
        ->assertSuccessful();

    expect(File::get($this->reportPath))->toContain(
        'https://legacy.example/wp-content/uploads/missing.jpg',
        'Onveilige link verwijderd',
        'Niet-ondersteunde iframe verwijderd',
    );

    $article->update(['content' => 'Handmatig aangepaste inhoud']);

    $this->pendingArtisan('wordpress:import', [
        'phase' => 'cleanup',
        '--manifest' => $this->manifestPath,
        '--report' => $this->reportPath,
    ])
        ->expectsOutputToContain('handmatige inhoud is niet overschreven')
        ->assertFailed();

    expect($article->refresh()->content)->toBe('Handmatig aangepaste inhoud');
});

test('it refreshes unchanged cleanup output from the verified source and repairs quoted attributes', function () {
    $rawContent = <<<'HTML'
        <p>Lees <a href="\&quot;https://legacy.example/over-ons/\&quot;">ons verhaal</a>.</p>
        <p>Bekijk <a href="\&quot;https://legacy.example/trainingen/\&quot;">de trainingen</a>.</p>
        <iframe src=\"https://www.youtube.com/embed/abc123\"></iframe>
        HTML;
    $article = Article::factory()->create([
        'slug' => 'indoor-seizoen',
        'content' => 'Eerder opgeschoonde inhoud.',
    ]);
    $manifest = wordpressCleanupManifest($article);
    data_set($manifest, 'mappings.posts.49916.content_checksum_sha256', hash('sha256', $rawContent));
    data_set($manifest, 'mappings.posts.49916.cleanup', [
        'output_checksum_sha256' => hash('sha256', $article->content),
        'unresolved_links' => ['https://legacy.example/\\"https://legacy.example/over-ons/\\"'],
        'missing_media' => [],
        'suspicious_markup' => [],
    ]);
    data_set($manifest, 'mappings.pages.49704', [
        'source_url' => 'https://legacy.example/trainingen/',
        'target' => ['type' => 'route', 'path' => '/events?type=training'],
    ]);
    writeWordPressCleanupManifest($this->manifestPath, $manifest);
    Http::fake([
        'https://legacy.example/wp-json/wp/v2/posts/49916' => Http::response([
            'id' => 49916,
            'content' => ['rendered' => $rawContent],
        ]),
    ]);

    $this->pendingArtisan('wordpress:import', [
        'phase' => 'cleanup',
        '--manifest' => $this->manifestPath,
        '--report' => $this->reportPath,
        '--refresh-source' => true,
    ])
        ->expectsOutputToContain('opgeschoond')
        ->assertSuccessful();

    expect($article->refresh()->content)
        ->toContain('ons verhaal (/about)')
        ->toContain('de trainingen (/events?type=training)')
        ->toContain('Video: https://www.youtube.com/embed/abc123')
        ->not->toContain('\\"', '&quot;')
        ->and(data_get(json_decode(File::get($this->manifestPath), true), 'mappings.posts.49916.cleanup.unresolved_links'))
        ->toBe([]);
});

/** @return array<string, mixed> */
function wordpressCleanupManifest(
    Article $article,
    ?Article $linkedArticle = null,
    ?MediaAsset $mediaAsset = null,
): array {
    $manifest = [
        'source' => [
            'posts_endpoint' => 'https://legacy.example/wp-json/wp/v2/posts',
            'pages_endpoint' => 'https://legacy.example/wp-json/wp/v2/pages',
        ],
        'mappings' => [
            'posts' => [
                '49916' => [
                    'article_id' => $article->id,
                    'source_url' => 'https://legacy.example/2025/indoor-seizoen/',
                    'content_checksum_sha256' => hash('sha256', $article->content),
                ],
            ],
            'pages' => [
                '316' => [
                    'source_url' => 'https://legacy.example/over-ons/',
                    'target' => ['type' => 'route', 'path' => '/about'],
                ],
            ],
        ],
    ];

    if ($linkedArticle instanceof Article) {
        data_set($manifest, 'mappings.posts.49917', [
            'article_id' => $linkedArticle->id,
            'source_url' => 'https://legacy.example/2024/ander-bericht/',
            'content_checksum_sha256' => hash('sha256', $linkedArticle->content),
        ]);
    }

    if ($mediaAsset instanceof MediaAsset) {
        data_set($manifest, 'mappings.media.49925', [
            'media_asset_id' => $mediaAsset->id,
            'source_url' => 'https://legacy.example/wp-content/uploads/2025/drone.jpg',
        ]);
    }

    return $manifest;
}

/** @param array<string, mixed> $manifest */
function writeWordPressCleanupManifest(string $manifestPath, array $manifest): void
{
    File::put(
        $manifestPath,
        json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
    );
}
