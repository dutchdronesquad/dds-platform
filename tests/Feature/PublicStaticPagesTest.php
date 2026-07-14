<?php

use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

test('homepage exposes its backend-backed conversion content', function () {
    $homepage = config('homepage');

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('welcome')
            ->where('upcomingEvents', $homepage['upcomingEvents'])
            ->where('upcomingEventsArePlaceholder', true)
            ->where('latestNews', $homepage['latestNews'])
            ->where('latestNewsAreLegacy', true)
            ->where('partnerLogos', $homepage['partnerLogos']),
        );
});

test('temporary homepage content is owned by the homepage config', function () {
    $homepage = config('homepage');

    expect(array_keys($homepage))->toBe([
        'upcomingEvents',
        'upcomingEventsArePlaceholder',
        'latestNews',
        'latestNewsAreLegacy',
        'partnerLogos',
    ])
        ->and($homepage['upcomingEvents'])->toHaveCount(3)
        ->and($homepage['latestNews'])->toHaveCount(3)
        ->and($homepage['partnerLogos'])->toHaveCount(1);

    foreach ($homepage['latestNews'] as $newsItem) {
        expect(public_path(ltrim($newsItem['image']['src'], '/')))->toBeFile();
    }

    foreach ($homepage['partnerLogos'] as $partnerLogo) {
        expect($partnerLogo)
            ->toHaveKeys(['alt', 'href', 'src'])
            ->and(public_path(ltrim($partnerLogo['src'], '/')))
            ->toBeFile();
    }
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
        ->and(public_path('images/dds/racing/homepage-hero.jpg'))
        ->toBeFile()
        ->and(public_path('images/dds/racing/indoor-track.jpg'))
        ->toBeFile()
        ->and(public_path('images/dds/racing/pilot-preparing-drone.jpg'))
        ->toBeFile()
        ->and(public_path('images/dds/racing/pilot-at-training.jpg'))
        ->toBeFile()
        ->and(public_path('images/dds/racing/race-control.jpg'))
        ->toBeFile()
        ->and(public_path('images/dds/racing/training-community.jpg'))
        ->toBeFile()
        ->and(public_path('images/dds/locations/sporthal-koggenhal.jpg'))
        ->toBeFile()
        ->and(public_path('images/dds/locations/sporthal-oosterhout.jpg'))
        ->toBeFile()
        ->and(public_path('images/dds/team/team-klaas.png'))
        ->toBeFile()
        ->and(public_path('images/dds/team/team-nico.png'))
        ->toBeFile()
        ->and(public_path('images/dds/partners/partner-droneshop.png'))
        ->toBeFile();
});

test('public navigation keeps private authentication links out of the public shell', function () {
    $publicLayout = file_get_contents(resource_path('js/layouts/public-layout.tsx'));

    if (! is_string($publicLayout)) {
        $this->fail('The public layout could not be read.');
    }

    $headerNavigationMatched = preg_match('/const headerNavItems: PublicNavItem\[\] = \[(.*?)\];/s', $publicLayout, $headerNavigation);
    $mobileNavigationMatched = preg_match('/const mobileNavItems: PublicNavItem\[\] = \[(.*?)\];/s', $publicLayout, $mobileNavigation);

    expect($headerNavigationMatched)
        ->toBe(1)
        ->and($mobileNavigationMatched)
        ->toBe(1)
        ->and($headerNavigation)
        ->toHaveKey(1)
        ->and($mobileNavigation)
        ->toHaveKey(1);

    $headerNavigationItems = $headerNavigation[1] ?? '';
    $mobileNavigationItems = $mobileNavigation[1] ?? '';

    expect($publicLayout)
        ->not->toContain('login()')
        ->not->toContain('dashboard()')
        ->not->toContain('Inloggen')
        ->not->toContain('Beheer')
        ->and($headerNavigationItems)
        ->toMatch('/Projecten.*Nieuws.*Over DDS.*Locaties.*Contact/s')
        ->not->toContain('Huisregels')
        ->and($mobileNavigationItems)
        ->toMatch('/Projecten.*Nieuws.*Over DDS.*Locaties.*Contact/s')
        ->not->toContain('Huisregels');
});

test('homepage partner section only contains verified logos', function () {
    $homepage = file_get_contents(resource_path('js/pages/welcome.tsx'));

    expect($homepage)
        ->not->toBeFalse()
        ->toContain('Partners & sponsors')
        ->toContain('Bekijk website van')
        ->not->toContain('Met dank aan de partijen')
        ->not->toContain('Demo, workshop of raceformat')
        ->not->toContain('Samen iets organiseren?')
        ->not->toContain('Bespreek een idee');
});

test('partner logo uses a tightly cropped canvas for visual alignment', function () {
    $dimensions = getimagesize(public_path('images/dds/partners/partner-droneshop.png'));

    expect($dimensions)
        ->not->toBeFalse()
        ->and($dimensions[0])
        ->toBe(947)
        ->and($dimensions[1])
        ->toBe(95);
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
