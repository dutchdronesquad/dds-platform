<?php

use App\Models\Article;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

test('homepage exposes its latest published articles alongside database events and catalogue partners', function () {
    $newest = Article::factory()->published()->create([
        'title' => 'Nieuw seizoen van start',
        'published_at' => now()->subDay(),
    ]);
    Article::factory()->published()->create(['published_at' => now()->subWeek()]);
    Article::factory()->published()->create(['published_at' => now()->subMonth()]);
    Article::factory()->create();
    Article::factory()->archived()->create();

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('welcome')
            ->has('upcomingEvents', 0)
            ->has('latestNews', 3)
            ->where('latestNews.0.title', $newest->title)
            ->has('partners', 2)
            ->where('partners.0.name', 'Droneshop.nl')
            ->where('partners.1.name', 'Sportpaleis Alkmaar')
        );
});

test('the homepage news teaser is absent without published articles', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('latestNews', []),
        );
});

test('the house rules route renders its dedicated code-owned page', function () {
    $this->get(route('house_rules'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/house-rules')
            ->missing('page')
            ->where('seo.title', 'Huisregels')
            ->where('seo.canonicalUrl', rtrim((string) config('app.url'), '/').'/house-rules'),
        );
});

test('the about route renders its dedicated code-owned page', function () {
    $this->get(route('about'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/about')
            ->missing('page')
            ->where('seo.title', 'Dutch Drone Squad')
            ->where('seo.canonicalUrl', rtrim((string) config('app.url'), '/').'/about'),
        );
});
