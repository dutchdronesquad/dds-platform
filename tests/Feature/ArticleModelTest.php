<?php

use App\Enums\ArticleCategory;
use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\MediaAsset;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

test('articles expose their domain casts and optional relationships', function () {
    $author = User::factory()->create();
    $coverImage = MediaAsset::factory()->create();
    $article = Article::query()
        ->create([
            'author_id' => $author->id,
            'cover_image_id' => $coverImage->id,
            'title' => 'Winter race report',
            'slug' => 'winter-race-report',
            'content' => 'A complete report of the winter race.',
            'published_at' => '2026-01-15 12:00:00',
            'status' => ArticleStatus::Published->value,
            'category' => ArticleCategory::RaceReport->value,
        ])
        ->refresh()
        ->load(['author', 'coverImage']);

    $this->assertModelExists($article);

    expect($article)
        ->title->toBe('Winter race report')
        ->slug->toBe('winter-race-report')
        ->content->toBe('A complete report of the winter race.')
        ->published_at->toBeInstanceOf(CarbonImmutable::class)
        ->status->toBe(ArticleStatus::Published)
        ->category->toBe(ArticleCategory::RaceReport)
        ->author->id->toBe($author->id)
        ->coverImage->id->toBe($coverImage->id);

    expect($author->articles()->whereKey($article->id)->exists())->toBeTrue();
});

test('article enum values are enforced by the database', function (string $column) {
    $article = Article::factory()->create();

    expect(fn () => DB::table($article->getTable())
        ->where('id', $article->id)
        ->update([$column => 'unsupported']))
        ->toThrow(QueryException::class);
})->with([
    'status' => 'status',
    'category' => 'category',
]);

test('new articles default to unpublished news', function () {
    $article = new Article;

    expect($article->status)->toBe(ArticleStatus::Draft)
        ->and($article->category)->toBe(ArticleCategory::News);
});

test('only published articles whose publication date has passed are publicly visible', function () {
    $visibleArticle = Article::factory()->published()->create([
        'published_at' => now()->subMinute(),
    ]);
    Article::factory()->create([
        'published_at' => now()->subDay(),
    ]);
    Article::factory()->archived()->create([
        'published_at' => now()->subDay(),
    ]);
    Article::factory()->published()->create([
        'published_at' => now()->addDay(),
    ]);
    Article::factory()->published()->create([
        'published_at' => null,
    ]);

    $publicArticles = Article::query()->publiclyVisible()->get();

    expect($publicArticles)->toHaveCount(1)
        ->and($publicArticles->first()->is($visibleArticle))->toBeTrue();
});

test('articles can preserve content without a platform author', function () {
    $article = Article::factory()->withoutAuthor()->create();

    expect($article->author_id)->toBeNull()
        ->and($article->author)->toBeNull();
});

test('deleting an author preserves the article and clears the reference', function () {
    $article = Article::factory()->create()->load('author');
    $author = $article->author;

    $author->delete();

    expect($article->refresh()->author_id)->toBeNull();
});

test('deleting a cover image preserves the article and clears the reference', function () {
    $article = Article::factory()->withCoverImage()->create()->load('coverImage');
    $coverImage = $article->coverImage;

    $coverImage->delete();

    expect($article->refresh()->cover_image_id)->toBeNull();
});
