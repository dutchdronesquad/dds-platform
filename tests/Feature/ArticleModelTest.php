<?php

use App\Enums\ArticleCategory;
use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\MediaAsset;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('articles can be created through their factory', function () {
    $article = Article::factory()
        ->published()
        ->raceReport()
        ->withCoverImage()
        ->create()
        ->load(['author', 'coverImage']);

    $this->assertModelExists($article);

    expect($article)
        ->title->toBeString()
        ->slug->toBeString()
        ->content->toBeString()
        ->published_at->toBeInstanceOf(CarbonImmutable::class)
        ->status->toBe(ArticleStatus::Published)
        ->category->toBe(ArticleCategory::RaceReport)
        ->author->toBeInstanceOf(User::class)
        ->coverImage->toBeInstanceOf(MediaAsset::class);
});

test('article content is stored without a separate excerpt column', function () {
    expect(Schema::hasColumn((new Article)->getTable(), 'excerpt'))->toBeFalse();
});

test('article enum values cover the supported domain states', function () {
    expect(array_column(ArticleStatus::cases(), 'value'))->toBe([
        'draft',
        'published',
        'archived',
    ])->and(array_column(ArticleCategory::cases(), 'value'))->toBe([
        'news',
        'announcement',
        'community',
        'race_report',
    ]);
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

test('new articles mirror their database defaults before persistence', function () {
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
