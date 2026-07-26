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

test('public static routes expose their page contract', function (string $routeName, string $expectedTitle) {
    $this->get(route($routeName))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/shell')
            ->where('page.title', $expectedTitle)
            ->has('page.description')
            ->has('page.primaryAction.label')
            ->has('page.primaryAction.href')
            ->has('page.visual.src')
            ->has('page.visual.alt')
            ->has('page.sections', 2)
            ->has('page.sections.0.heading')
            ->has('page.sections.0.body'),
        );
})->with([
    'about' => ['about', 'About'],
    'house rules' => ['house_rules', 'House Rules'],
]);
