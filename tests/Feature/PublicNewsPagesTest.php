<?php

use App\Enums\ArticleCategory;
use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\MediaAsset;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

test('the news index lists only published articles with an excerpt', function () {
    $published = Article::factory()->published()->create([
        'title' => 'Nieuw seizoen van start',
        'content' => "Dit seizoen gaan we weer **racen** op de baan in Alkmaar.\n\n![Indoor race](<https://media.example/indoor-race.jpg>)",
        'published_at' => now()->subDay(),
    ]);
    Article::factory()->create(['title' => 'Conceptartikel']);
    Article::factory()->archived()->create(['title' => 'Oud artikel']);
    Article::factory()->create([
        'title' => 'Toekomstig artikel',
        'status' => ArticleStatus::Published,
        'published_at' => now()->addDay(),
    ]);

    $this->get(route('news.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/news-index')
            ->where('activeCategory', null)
            ->has('articles.data', 1)
            ->where('articles.data.0.id', $published->id)
            ->where('articles.data.0.title', 'Nieuw seizoen van start')
            ->where(
                'articles.data.0.excerpt',
                'Dit seizoen gaan we weer racen op de baan in Alkmaar.',
            )
            ->has('categoryFilters', 4)
            ->has('seo.title'),
        );
});

test('the news index provides a useful empty state', function () {
    $this->get(route('news.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('articles.data', [])
            ->has('seo.title'),
        );
});

test('the news index filters published articles by category', function () {
    $announcement = Article::factory()->published()->create([
        'category' => ArticleCategory::Announcement,
    ]);
    Article::factory()->published()->create([
        'category' => ArticleCategory::RaceReport,
    ]);

    $this->get(route('news.index', ['category' => ArticleCategory::Announcement->value]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/news-index')
            ->where('activeCategory', ArticleCategory::Announcement->value)
            ->where('articles.total', 1)
            ->has('articles.data', 1)
            ->where('articles.data.0.id', $announcement->id)
            ->where('articles.data.0.category', ArticleCategory::Announcement->value),
        );
});

test('an unsupported news category falls back to the unfiltered index', function () {
    Article::factory()->published()->create();

    $this->get(route('news.index', ['category' => 'unsupported']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('activeCategory', null)
            ->where('articles.total', 1),
        );
});

test('an article detail exposes content, category and author', function () {
    $author = User::factory()->create(['name' => 'Jane Pilot']);
    $coverImage = MediaAsset::factory()
        ->named('nieuw-seizoen.jpg')
        ->create([
            'alt_text' => ['en' => 'Pilots preparing for the new season'],
        ]);
    $article = Article::factory()->published()->create([
        'author_id' => $author->id,
        'title' => 'Nieuw seizoen van start',
        'slug' => 'nieuw-seizoen-van-start',
        'content' => "Dit seizoen gaan we weer **racen** op de baan in Alkmaar.\n\n![Indoor race](<https://media.example/indoor-race.jpg>)",
        'category' => ArticleCategory::Announcement,
        'cover_image_id' => $coverImage->id,
    ]);

    $this->get(route('news.show', ['article' => $article->slug]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/article-show')
            ->where('article.id', $article->id)
            ->where('article.title', 'Nieuw seizoen van start')
            ->where('article.slug', 'nieuw-seizoen-van-start')
            ->where(
                'article.contentHtml',
                "<p>Dit seizoen gaan we weer <strong>racen</strong> op de baan in Alkmaar.</p>\n<p><img src=\"https://media.example/indoor-race.jpg\" alt=\"Indoor race\" /></p>\n",
            )
            ->where('article.category', ArticleCategory::Announcement->value)
            ->where('article.author.name', 'Jane Pilot')
            ->where('article.image.src', $coverImage->url())
            ->where('article.image.alt', 'Pilots preparing for the new season')
            ->where('relatedArticles', [])
            ->where('seo.title', 'Nieuw seizoen van start'),
        );
});

test('an article detail exposes the latest other published articles for the sidebar', function () {
    $article = Article::factory()->published()->create([
        'slug' => 'huidig-artikel',
        'published_at' => now(),
    ]);
    $newest = Article::factory()->published()->create([
        'title' => 'Laatste nieuws',
        'published_at' => now()->subDay(),
    ]);
    $second = Article::factory()->published()->create([
        'title' => 'Tweede nieuwsitem',
        'published_at' => now()->subDays(2),
    ]);
    $third = Article::factory()->published()->create([
        'title' => 'Derde nieuwsitem',
        'published_at' => now()->subDays(3),
    ]);
    Article::factory()->published()->create([
        'title' => 'Te oud voor de sidebar',
        'published_at' => now()->subDays(4),
    ]);
    Article::factory()->create(['title' => 'Conceptartikel']);
    Article::factory()->published()->create([
        'title' => 'Nog niet zichtbaar',
        'published_at' => now()->addDay(),
    ]);

    $this->get(route('news.show', ['article' => $article->slug]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('relatedArticles', 3)
            ->where('relatedArticles.0.id', $newest->id)
            ->where('relatedArticles.0.title', 'Laatste nieuws')
            ->where('relatedArticles.1.id', $second->id)
            ->where('relatedArticles.2.id', $third->id)
            ->missing('relatedArticles.3'),
        );
});

test('a draft article is not found', function () {
    $article = Article::factory()->create(['slug' => 'concept-artikel']);

    $this->get(route('news.show', ['article' => $article->slug]))
        ->assertNotFound();
});

test('an archived article is not found', function () {
    $article = Article::factory()->archived()->create(['slug' => 'oud-artikel']);

    $this->get(route('news.show', ['article' => $article->slug]))
        ->assertNotFound();
});

test('a scheduled article is not found before its publication date', function () {
    $article = Article::factory()->published()->create([
        'slug' => 'toekomstig-artikel',
        'published_at' => now()->addDay(),
    ]);

    $this->get(route('news.show', ['article' => $article->slug]))
        ->assertNotFound();
});

test('an unknown article slug is not found', function () {
    $this->get('/news/unknown-article')->assertNotFound();
});
