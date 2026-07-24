<?php

use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

test('homepage exposes its temporary news and partner contract alongside database events', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('welcome')
            ->has('upcomingEvents', 0)
            ->has('latestNews', 3)
            ->where('latestNewsAreLegacy', true)
            ->has('partnerLogos', 1)
            ->has('partnerLogos.0', fn (Assert $partner) => $partner
                ->where('alt', 'Droneshop.nl')
                ->where('href', 'https://droneshop.nl')
                ->has('src'),
            ),
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
    'news' => ['news.index', 'News'],
    'locations' => ['locations.index', 'Locations'],
    'about' => ['about', 'About'],
    'house rules' => ['house_rules', 'House Rules'],
    'partners' => ['partners', 'Partners'],
    'contact' => ['contact', 'Contact'],
]);
