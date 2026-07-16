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
            ->has('upcomingEvents', 0)
            ->where('latestNews', $homepage['latestNews'])
            ->where('latestNewsAreLegacy', true)
            ->where('partnerLogos', $homepage['partnerLogos']),
        );
});

test('temporary homepage content is owned by the homepage config', function () {
    $homepage = config('homepage');

    expect(array_keys($homepage))->toBe([
        'latestNews',
        'latestNewsAreLegacy',
        'partnerLogos',
    ])
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
        ->and(public_path('images/dds/racing/sportpaleis-empty.jpg'))
        ->toBeFile()
        ->and(public_path('images/dds/racing/sportpaleis-empty-leveled.jpg'))
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
        ->not->toContain('bg-paper/96')
        ->not->toContain('isHome')
        ->toContain('absolute inset-x-0 top-0 z-50 border-b border-white/10 bg-linear-to-b from-ink/82 to-ink/38 backdrop-blur-lg')
        ->toContain('lg:sticky lg:-mb-18')
        ->toContain('isHeaderScrolled &&')
        ->toContain('let animationFrameId: number | null = null;')
        ->toContain("window.addEventListener('scroll', scheduleHeaderUpdate")
        ->toContain("window.removeEventListener('scroll', scheduleHeaderUpdate)")
        ->toContain('window.cancelAnimationFrame(animationFrameId)')
        ->toContain('lg:border-white/12 lg:bg-ink/68 lg:bg-none lg:shadow-lg lg:shadow-ink/10 lg:backdrop-blur-xl')
        ->toMatch('/<PublicBrand\s+inverse\s+/')
        ->and($headerNavigationItems)
        ->toMatch('/Projecten.*Nieuws.*Over DDS.*Locaties.*Contact/s')
        ->not->toContain('Huisregels')
        ->and($mobileNavigationItems)
        ->toMatch('/Projecten.*Nieuws.*Over DDS.*Locaties.*Contact/s')
        ->not->toContain('Huisregels');
});

test('public footer links to the official social channels', function () {
    $publicLayout = file_get_contents(resource_path('js/layouts/public-layout.tsx'));

    if (! is_string($publicLayout)) {
        $this->fail('The public layout could not be read.');
    }

    expect($publicLayout)
        ->toContain('https://www.instagram.com/dutchdronesquad/')
        ->toContain('https://www.facebook.com/dutchdronesquad')
        ->toContain('https://www.youtube.com/@dutchdronesquad')
        ->toContain('https://www.twitch.tv/dutchdronesquad')
        ->toContain('Volg Dutch Drone Squad op ${item.title}')
        ->toContain('target="_blank"')
        ->toContain('rel="noopener noreferrer"')
        ->toContain('gap-10 px-public-gutter py-10 sm:py-12')
        ->toContain('lg:gap-10 lg:py-14')
        ->toContain('px-public-gutter py-4')
        ->toContain('flex size-10')
        ->toContain("import { ArrowUpRight, Menu, X } from 'lucide-react';")
        ->not->toContain('LucideIcon');
});

test('public event polish keeps filtering in place and provides a simple empty state', function () {
    $eventsPage = file_get_contents(resource_path('js/pages/public/events-index.tsx'));
    $eventCard = file_get_contents(resource_path('js/components/public/public-event-card.tsx'));
    $publicPatterns = file_get_contents(resource_path('js/components/public/public-patterns.tsx'));
    $homepage = file_get_contents(resource_path('js/pages/welcome.tsx'));
    $eventFormatting = file_get_contents(resource_path('js/lib/event-formatting.ts'));

    expect($eventsPage)
        ->not->toBeFalse()
        ->not->toContain('kicker="Agenda"')
        ->toContain('aria-live="polite"')
        ->toContain('<ul')
        ->toContain('<li key={event.id}')
        ->toContain('variant="list"')
        ->toContain('divide-y divide-paddock-rule')
        ->toContain('items-center rounded-sm border px-4 py-2')
        ->not->toContain('items-center rounded-full border px-4 py-2')
        ->not->toContain('grid gap-6 md:grid-cols-2 xl:grid-cols-3')
        ->toMatch('/activeType !== null.*?<Link\s+href=\{eventsIndex\(\)\}\s+preserveScroll\s+preserveState.*?Bekijk alle events/s')
        ->and($eventCard)
        ->not->toBeFalse()
        ->toContain("variant?: 'card' | 'list'")
        ->toContain('<EventDateRail')
        ->toContain('<EventFacts event={event} variant="card" />')
        ->toContain('<EventFacts event={event} variant="list" />')
        ->toContain('{date.weekday}')
        ->toContain('date.year !== null')
        ->toContain('event.season !== null')
        ->toContain('{event.season.name}')
        ->toContain('<EventCardDate date={date} event={event} />')
        ->not->toContain('function EventCardMeta')
        ->toContain('aria-label={formatEventDate(event.startsAt)}')
        ->toContain('line-clamp-2')
        ->not->toContain('line-clamp-3')
        ->toContain('overflow-hidden rounded-sm border border-paddock-rule')
        ->not->toContain('overflow-hidden rounded-xl border border-paddock-rule')
        ->toContain('aspect-[5/2] w-full rounded-sm')
        ->not->toContain('aspect-[5/2] w-full rounded-lg')
        ->toContain('bg-deep-signal/[0.025]')
        ->toContain('<div className="mt-auto pt-6">')
        ->toContain('grid grid-cols-3 divide-x divide-paddock-rule')
        ->toContain('text-[0.7rem] whitespace-nowrap')
        ->toContain('<span className="hidden lg:inline"> totaal</span>')
        ->toContain("event.registrationStatus === 'waitlist'")
        ->toContain('rounded-md border px-3 py-1.5')
        ->toContain('<StatusIcon aria-hidden="true"')
        ->and($eventFormatting)
        ->not->toBeFalse()
        ->toContain('year: year === yearFormatter.format(referenceDate) ? null : year')
        ->and($publicPatterns)
        ->not->toBeFalse()
        ->toContain('max-w-2xl text-base leading-7 text-white/72')
        ->toContain('function HeroSeparator')
        ->toContain('text-paper dark:text-night-950')
        ->and($homepage)
        ->not->toBeFalse()
        ->not->toContain('function HeroRaceLine')
        ->toContain('separatorTone="air"')
        ->not->toContain('RadioTower')
        ->not->toContain('Planning in beweging')
        ->not->toContain('CalendarClock')
        ->not->toContain('TBA')
        ->not->toContain('Next heat')
        ->not->toContain('--:--')
        ->toContain('Train, race en verbeter jezelf op onze indoorbaan in Alkmaar.')
        ->toContain("label: 'Bekijk hoe we racen'")
        ->not->toContain('Even geen startlichten.')
        ->toContain('/images/dds/racing/sportpaleis-empty-leveled.jpg')
        ->toContain('Lege sportvloer in het Sportpaleis Alkmaar')
        ->toContain('De baan is even leeg.')
        ->toContain('Zodra de volgende racedag vaststaat,')
        ->toContain('vind je hem hier.')
        ->toContain('absolute top-0 right-0 h-1 w-1/5 bg-dds-cyan')
        ->toContain('events.length > 0 ?')
        ->not->toContain('Veeg voor meer')
        ->not->toContain('ArrowRight');
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
