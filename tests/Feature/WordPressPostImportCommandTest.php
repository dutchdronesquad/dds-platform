<?php

use App\Enums\ArticleCategory;
use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\MediaAsset;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

beforeEach(function () {
    Http::preventStrayRequests();
    $this->manifestPath = storage_path('app/wordpress-post-import-'.Str::uuid().'.json');
});

afterEach(function () {
    File::delete($this->manifestPath);
});

test('it previews selected and skipped posts without writing articles or mappings', function () {
    $teamAuthor = User::factory()->create();
    $coverImage = MediaAsset::factory()->create();

    writeWordPressPostManifest($this->manifestPath, wordpressPostManifest(
        authorId: $teamAuthor->id,
        mediaAssetId: $coverImage->id,
        posts: [
            wordpressPostSelection(49916),
            wordpressPostSelection(
                1158,
                slug: 'oude-race',
                title: 'Oud raceverslag',
                decision: 'skip',
                reason: 'Niet geselecteerd na inhoudsreview.',
            ),
        ],
    ));

    Http::fake([
        'legacy.example/wp-json/wp/v2/posts/49916' => Http::response(
            wordpressPostRecord(49916),
        ),
    ]);

    $exitCode = Artisan::call('wordpress:import', [
        'phase' => 'posts',
        '--manifest' => $this->manifestPath,
        '--dry-run' => true,
    ]);

    expect($exitCode)->toBe(0)
        ->and(Artisan::output())
        ->toContain('Dry-run', '49916', 'klaar', '1158', 'overgeslagen')
        ->and(Article::query()->count())->toBe(0)
        ->and(json_decode(File::get($this->manifestPath), true))->not->toHaveKey('mappings.posts');
});

test('it imports a post once and preserves editorial changes on repeated runs', function () {
    $teamAuthor = User::factory()->create(['name' => 'Team DDS']);
    $coverImage = MediaAsset::factory()->create();

    writeWordPressPostManifest($this->manifestPath, wordpressPostManifest(
        authorId: $teamAuthor->id,
        mediaAssetId: $coverImage->id,
    ));

    Http::fake([
        'legacy.example/wp-json/wp/v2/posts/49916' => Http::response(
            wordpressPostRecord(49916),
        ),
    ]);

    $this->pendingArtisan('wordpress:import', [
        'phase' => 'posts',
        '--manifest' => $this->manifestPath,
    ])
        ->expectsOutputToContain('geïmporteerd')
        ->assertSuccessful();

    $article = Article::query()->sole();
    $manifest = json_decode(File::get($this->manifestPath), true, flags: JSON_THROW_ON_ERROR);
    $mapping = data_get($manifest, 'mappings.posts.49916');

    expect($article)
        ->author_id->toBe($teamAuthor->id)
        ->cover_image_id->toBe($coverImage->id)
        ->title->toBe('Indoor seizoen 25/26')
        ->slug->toBe('seizoen-25-26')
        ->content->toBe('<p>We zijn klaar voor een nieuw indoorseizoen.</p>')
        ->status->toBe(ArticleStatus::Published)
        ->category->toBe(ArticleCategory::Announcement)
        ->and($article->published_at?->toIso8601String())->toBe('2025-09-18T10:15:00+00:00')
        ->and($mapping)->toMatchArray([
            'article_id' => $article->id,
            'source_url' => 'https://legacy.example/2025/09/18/seizoen-25-26/',
            'source_slug' => 'seizoen-25-26',
            'wordpress_author_id' => 7,
            'author_id' => $teamAuthor->id,
            'featured_media_id' => 49925,
            'category_ids' => [4],
            'tag_ids' => [8, 12],
        ])
        ->and($mapping['content_checksum_sha256'])
        ->toBe(hash('sha256', '<p>We zijn klaar voor een nieuw indoorseizoen.</p>'));

    $article->update(['title' => 'Redactioneel bijgewerkte titel']);

    $this->pendingArtisan('wordpress:import', [
        'phase' => 'posts',
        '--manifest' => $this->manifestPath,
    ])
        ->expectsOutputToContain('hergebruikt')
        ->assertSuccessful();

    expect(Article::query()->count())->toBe(1)
        ->and($article->refresh()->title)->toBe('Redactioneel bijgewerkte titel');

    Http::assertSentCount(1);
});

test('it reports missing media mappings and slug conflicts without creating articles', function () {
    $teamAuthor = User::factory()->create();
    Article::factory()->create(['slug' => 'seizoen-25-26']);

    writeWordPressPostManifest($this->manifestPath, wordpressPostManifest(
        authorId: $teamAuthor->id,
        mediaAssetId: null,
        posts: [
            wordpressPostSelection(49916),
            wordpressPostSelection(49917),
        ],
    ));

    Http::fake([
        'legacy.example/wp-json/wp/v2/posts/49916' => Http::response(
            wordpressPostRecord(49916),
        ),
        'legacy.example/wp-json/wp/v2/posts/49917' => Http::response(
            wordpressPostRecord(49917, featuredMediaId: 0),
        ),
    ]);

    $this->pendingArtisan('wordpress:import', [
        'phase' => 'posts',
        '--manifest' => $this->manifestPath,
    ])
        ->expectsOutputToContain('voer eerst de media-import uit')
        ->expectsOutputToContain('Slugconflict')
        ->assertFailed();

    expect(Article::query()->count())->toBe(1);
});

/**
 * @param  list<array<string, mixed>>|null  $posts
 * @return array<string, mixed>
 */
function wordpressPostManifest(int $authorId, ?int $mediaAssetId, ?array $posts = null): array
{
    $manifest = [
        'source' => [
            'posts_endpoint' => 'https://legacy.example/wp-json/wp/v2/posts',
        ],
        'defaults' => [
            'author_id' => $authorId,
        ],
        'posts' => $posts ?? [wordpressPostSelection(49916)],
    ];

    if ($mediaAssetId !== null) {
        data_set($manifest, 'mappings.media.49925.media_asset_id', $mediaAssetId);
    }

    return $manifest;
}

/** @return array<string, mixed> */
function wordpressPostSelection(
    int $wordpressId,
    string $slug = 'seizoen-25-26',
    string $title = 'Indoor seizoen 25/26',
    string $category = 'announcement',
    string $decision = 'import',
    ?string $reason = null,
): array {
    return [
        'wordpress_id' => $wordpressId,
        'slug' => $slug,
        'title' => $title,
        'published_at' => '2025-09-18T10:15:00Z',
        'category' => $category,
        'decision' => $decision,
        'reason' => $reason,
    ];
}

/** @return array<string, mixed> */
function wordpressPostRecord(int $id, int $featuredMediaId = 49925): array
{
    return [
        'id' => $id,
        'status' => 'publish',
        'link' => 'https://legacy.example/2025/09/18/seizoen-25-26/',
        'slug' => 'seizoen-25-26',
        'content' => [
            'rendered' => '<p>We zijn klaar voor een nieuw indoorseizoen.</p>',
        ],
        'author' => 7,
        'featured_media' => $featuredMediaId,
        'categories' => [4],
        'tags' => [8, 12],
    ];
}

/** @param array<string, mixed> $manifest */
function writeWordPressPostManifest(string $manifestPath, array $manifest): void
{
    File::put(
        $manifestPath,
        json_encode($manifest, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
    );
}
