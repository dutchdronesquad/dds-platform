<?php

use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

test('homepage exposes the upcoming events module contract', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('welcome')
            ->has('upcomingEvents', 0)
            ->where('upcomingEvent', null),
        );
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
            ->has('page.visual.src')
            ->has('page.visual.alt')
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
                'visual',
            ])
            ->and($publicPage['primaryAction'])
            ->toHaveKeys(['label', 'href'])
            ->and($publicPage['visual'])
            ->toHaveKeys(['src', 'alt'])
            ->and($publicPage['visual']['src'])
            ->toStartWith('/images/dds/')
            ->and(public_path(ltrim($publicPage['visual']['src'], '/')))
            ->toBeFile()
            ->and($publicPage['sections'])
            ->toHaveCount(2);
    }
});

test('public brand assets are available locally', function () {
    expect(public_path('brand/dds-logo.svg'))
        ->toBeFile()
        ->and(public_path('favicon.svg'))
        ->toBeFile()
        ->and(public_path('images/dds/homepage-hero.jpg'))
        ->toBeFile()
        ->and(public_path('images/dds/indoor-track.jpg'))
        ->toBeFile()
        ->and(public_path('images/dds/pilot-preparing-drone.jpg'))
        ->toBeFile()
        ->and(public_path('images/dds/pilot-at-training.jpg'))
        ->toBeFile()
        ->and(public_path('images/dds/race-control.jpg'))
        ->toBeFile()
        ->and(public_path('images/dds/training-community.jpg'))
        ->toBeFile()
        ->and(public_path('images/dds/sporthal-koggenhal.jpg'))
        ->toBeFile()
        ->and(public_path('images/dds/sporthal-oosterhout.jpg'))
        ->toBeFile()
        ->and(public_path('images/dds/team-klaas.png'))
        ->toBeFile()
        ->and(public_path('images/dds/team-nico.png'))
        ->toBeFile();
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
