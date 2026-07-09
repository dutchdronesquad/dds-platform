<?php

use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

test('public static shell pages render', function (string $routeName, string $pageKey) {
    $publicPage = config("public_pages.{$pageKey}");

    expect($publicPage)->toBeArray();

    $this->get(route($routeName))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/shell')
            ->where('page.title', $publicPage['title'])
            ->has('page.description')
            ->has('page.primaryAction.label')
            ->has('page.primaryAction.href')
            ->has('page.sections', 2)
            ->has('page.sections.0.heading')
            ->has('page.sections.0.body'),
        );
})->with([
    'events' => ['events.index', 'events'],
    'projects' => ['projects.index', 'projects'],
    'news' => ['news.index', 'news'],
    'locations' => ['locations.index', 'locations'],
    'about' => ['about', 'about'],
    'house rules' => ['house_rules', 'house_rules'],
    'partners' => ['partners', 'partners'],
    'contact' => ['contact', 'contact'],
]);

test('public shell page copy is owned by the public pages config', function () {
    $publicPages = config('public_pages');

    expect(array_keys($publicPages))->toBe([
        'events',
        'projects',
        'news',
        'locations',
        'about',
        'house_rules',
        'partners',
        'contact',
    ]);

    foreach ($publicPages as $publicPage) {
        expect($publicPage)
            ->toHaveKeys([
                'title',
                'eyebrow',
                'description',
                'primaryAction',
                'sections',
            ])
            ->and($publicPage['primaryAction'])
            ->toHaveKeys(['label', 'href'])
            ->and($publicPage['sections'])
            ->toHaveCount(2);
    }
});

test('projects page frames projects as a public showcase', function () {
    $this->get(route('projects.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/shell')
            ->where('page.eyebrow', 'Showcase')
            ->where('page.description', 'Publieke showcase voor DDS-built tooling, software, plugins, apps, integraties en geselecteerde community builds.')
            ->where('page.sections.0.heading', 'Geen intern projectbeheer'),
        );
});

test('event detail placeholder renders with the requested slug', function () {
    $this->get(route('events.show', ['slug' => 'winter-training']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/event-show')
            ->where('slug', 'winter-training'),
        );
});
