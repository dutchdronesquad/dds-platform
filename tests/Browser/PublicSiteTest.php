<?php

use App\Enums\EventRegistrationStatus;
use App\Enums\EventType;
use App\Models\Event;
use App\Models\Location;
use App\Models\Season;
use App\Models\SeasonTicket;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Vite;

beforeEach(function () {
    Carbon::setTestNow('2026-07-16 10:00:00');
    Vite::useHotFile(storage_path('framework/testing/vite.hot'));
});

afterEach(function () {
    Carbon::setTestNow();
});

test('desktop visitors can use the public shell and safe external links', function () {
    $page = visit('/')
        ->on()->desktop()
        ->withTimezone('Europe/Amsterdam');

    $page->assertNoJavaScriptErrors()
        ->assertSee('Where racing brings pilots together.')
        ->assertSee('De baan is even leeg.')
        ->assertSee('Partners & sponsors')
        ->assertDontSee('Inloggen')
        ->assertDontSee('Beheer')
        ->assertVisible('nav[aria-label="Hoofdnavigatie"]')
        ->assertMissing('button[aria-label="Open navigatie"]')
        ->assertAttribute(
            'a[aria-label="Volg Dutch Drone Squad op Instagram"]',
            'href',
            'https://www.instagram.com/dutchdronesquad/',
        )
        ->assertAttribute(
            'a[aria-label="Volg Dutch Drone Squad op Instagram"]',
            'target',
            '_blank',
        )
        ->assertAttribute(
            'a[aria-label="Volg Dutch Drone Squad op Instagram"]',
            'rel',
            'noopener noreferrer',
        )
        ->assertAttribute(
            'a[aria-label="Bekijk website van Droneshop.nl"]',
            'href',
            'https://droneshop.nl',
        )
        ->assertAttribute(
            'a[aria-label="Bekijk website van Droneshop.nl"]',
            'target',
            '_blank',
        )
        ->keys('[class~="font-sans"]', 'Tab')
        ->assertScript(
            "document.activeElement?.getAttribute('aria-label')",
            'Dutch Drone Squad home',
        )
        ->assertScript(
            "(() => { const element = document.activeElement; const style = getComputedStyle(element); return element.matches(':focus-visible') && (style.outlineStyle !== 'none' || style.boxShadow !== 'none'); })()",
        )
        ->assertNoSmoke();
});

test('mobile navigation opens, reflows, and follows public links', function () {
    $page = visit('/')
        ->on()->iPhone14Pro()
        ->withTimezone('Europe/Amsterdam');

    $page->assertVisible('button[aria-label="Open navigatie"]')
        ->assertMissing('nav[aria-label="Hoofdnavigatie"]')
        ->assertAriaAttribute(
            'button[aria-label="Open navigatie"]',
            'expanded',
            'false',
        )
        ->click('button[aria-label="Open navigatie"]')
        ->assertVisible('#mobile-public-navigation')
        ->assertAriaAttribute(
            'button[aria-label="Sluit navigatie"]',
            'expanded',
            'true',
        )
        ->assertSeeIn('#mobile-public-navigation', 'Projecten')
        ->assertSeeIn('#mobile-public-navigation', 'Contact')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->click('#mobile-public-navigation a[href="/contact"]')
        ->assertPathIs('/contact')
        ->assertMissing('#mobile-public-navigation')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->assertNoSmoke();
});

test('visitors can filter rendered event states and recover from an empty result', function () {
    $season = Season::factory()->create([
        'name' => 'Indoor trainingsseizoen 2026/2027',
    ]);
    SeasonTicket::factory()->available()->for($season)->create();
    Event::factory()->published()->training()->for($season)->create([
        'title' => 'Open training',
        'starts_at' => '2026-10-15 17:00:00',
        'registration_status' => EventRegistrationStatus::Open,
    ]);
    Event::factory()->published()->create([
        'title' => 'Volle race',
        'starts_at' => '2026-10-22 17:00:00',
        'type' => EventType::Race,
        'registration_status' => EventRegistrationStatus::Full,
    ]);
    Event::factory()->published()->create([
        'title' => 'Wachtlijst workshop',
        'starts_at' => '2026-10-29 17:00:00',
        'type' => EventType::Workshop,
        'registration_status' => EventRegistrationStatus::Waitlist,
    ]);
    Event::factory()->published()->create([
        'title' => 'Inschrijving opent later',
        'starts_at' => '2026-11-01 17:00:00',
        'registration_opens_at' => '2026-10-19 07:00:00',
        'registration_status' => EventRegistrationStatus::Closed,
    ]);
    Event::factory()->cancelled()->create([
        'title' => 'Geannuleerde race',
        'starts_at' => '2026-11-05 17:00:00',
        'published_at' => '2026-07-01 10:00:00',
        'type' => EventType::Race,
    ]);

    $page = visit('/events')
        ->on()->desktop()
        ->withTimezone('Europe/Amsterdam');

    $page->assertSee('Open training')
        ->assertSee('Volle race')
        ->assertSee('Wachtlijst workshop')
        ->assertSee('Inschrijving opent later')
        ->assertSee('Geannuleerde race')
        ->assertDontSee('Actief seizoen')
        ->assertDontSee('Seizoen op de agenda')
        ->assertDontSee('Seizoensticket')
        ->assertDontSee('Verkoop open')
        ->assertSee('Indoor trainingsseizoen 2026/2027')
        ->assertSee('1 event')
        ->assertAttribute(
            "a[href=\"/seasons/{$season->slug}\"]",
            'href',
            "/seasons/{$season->slug}",
        )
        ->assertSee('Aanmelden mogelijk')
        ->assertSee('Vol')
        ->assertSee('Wachtlijst')
        ->assertSee('Nog niet geopend')
        ->assertDontSee('Aanmelding gesloten')
        ->assertSee('Geannuleerd')
        ->assertScript(
            "[...document.querySelectorAll('[data-testid=\"event-list-registration\"]')].every((container) => { const status = container.querySelector('span'); return status !== null && Math.round(status.getBoundingClientRect().height) <= 34 && status.scrollWidth <= status.clientWidth; })",
        )
        ->click('Trainingen')
        ->assertQueryStringHas('type', 'training')
        ->assertAriaAttribute(
            'nav[aria-label="Filter events op type"] a[href="/events?type=training"]',
            'current',
            'page',
        )
        ->assertSee('Open training')
        ->assertDontSee('Volle race')
        ->assertDontSee('Actief seizoen')
        ->assertSee('Indoor trainingsseizoen 2026/2027')
        ->assertSee('1 training')
        ->click('Demo’s')
        ->assertQueryStringHas('type', 'demo')
        ->assertSee('Geen events gevonden')
        ->assertSee('Er zijn geen aankomende events van dit type.')
        ->click('Alles')
        ->assertQueryStringMissing('type')
        ->assertSee('Open training')
        ->assertNoSmoke();
});

test('event details render long content, dates, registration, and safe links on mobile', function () {
    $location = Location::factory()->create([
        'name' => 'Sportpaleis Alkmaar',
        'street' => 'Terborchlaan',
        'house_number' => '200',
        'postal_code' => '1816 LE',
        'city' => 'Alkmaar',
    ]);
    $season = Season::factory()->create([
        'name' => 'DDS Wintercompetitie voor gevorderde indoorpiloten 2026/2027',
    ]);
    $event = Event::factory()->for($location)->for($season)->published()->training()->create([
        'title' => 'Lange indoor briefing',
        'slug' => 'lange-indoor-briefing',
        'content' => 'Start van de briefing. '.str_repeat(
            'Neem je racequad, goggles en voldoende accu’s mee. ',
            30,
        ).'Einde van de briefing.',
        'starts_at' => '2026-10-15 17:00:00',
        'ends_at' => '2026-10-15 20:30:00',
        'capacity' => 16,
        'price_cents' => 1500,
        'registration_opens_at' => '2026-09-15 10:00:00',
        'registration_deadline_at' => '2026-10-14 23:59:00',
        'registration_status' => EventRegistrationStatus::Open,
        'registration_url' => 'https://example.com/registration',
    ]);
    $seasonFinale = Event::factory()->for($location)->for($season)->published()->training()->create([
        'title' => 'Finale van de wintercompetitie',
        'slug' => 'finale-wintercompetitie',
        'starts_at' => '2027-05-20 17:00:00',
        'ends_at' => '2027-05-20 20:30:00',
    ]);
    $springEvent = Event::factory()->for($location)->for($season)->published()->training()->create([
        'title' => 'Voorjaarsronde van de wintercompetitie',
        'slug' => 'voorjaarsronde-wintercompetitie',
        'starts_at' => '2027-03-18 17:00:00',
        'ends_at' => '2027-03-18 20:30:00',
        'price_cents' => 2500,
        'registration_status' => EventRegistrationStatus::Open,
        'registration_url' => 'https://example.com/spring-registration',
    ]);
    SeasonTicket::factory()->available()->for($season)->create([
        'copy' => 'Toegang tot alle competitierondes.',
        'price_cents' => 9_000,
        'registration_url' => 'https://example.com/season-ticket',
    ]);

    $desktopPage = visit("/events/{$event->slug}")
        ->on()->desktop()
        ->withTimezone('Europe/Amsterdam');

    $desktopPage->assertScript(
        "(() => { const items = [...document.querySelectorAll('[data-testid=\"event-quick-facts\"] > div')]; const widths = items.map((item) => Math.round(item.getBoundingClientRect().width)); return items.length === 4 && new Set(widths).size === 1; })()",
    );

    $page = visit("/events/{$event->slug}")
        ->on()->iPhone14Pro()
        ->withTimezone('Europe/Amsterdam');

    $page->assertSee('Lange indoor briefing')
        ->assertSee('donderdag 15 oktober 2026')
        ->assertSee('Sportpaleis Alkmaar')
        ->assertDontSee('Sportpaleis Alkmaar, Alkmaar')
        ->assertSee('Start van de briefing.')
        ->assertSee('Einde van de briefing.')
        ->assertSee('Aanmelden mogelijk')
        ->assertScript(
            '(document.body.innerText.match(/Aanmelden mogelijk/g) ?? []).length === 1',
        )
        ->assertScript(
            "document.querySelector('#praktische-info').textContent.includes('Los ticket') && document.querySelector('#praktische-info').textContent.includes('15,00') && !document.querySelector('#tickets').textContent.includes('15,00')",
        )
        ->assertSee('16 plekken totaal')
        ->assertSee('Aanmelden vanaf')
        ->assertSee('Aanmelden tot')
        ->assertSee('Aanmelden voor dit event.')
        ->assertSee('Je meldt je hiermee aan voor Lange indoor briefing.')
        ->assertDontSee('Deze inschrijving geldt alleen voor')
        ->assertSee('Seizoen')
        ->assertSee('DDS Wintercompetitie voor gevorderde indoorpiloten 2026/2027')
        ->assertSee('Ook in seizoensticket')
        ->assertSee('Bekijk seizoen')
        ->assertDontSee('Onderdeel van het seizoen')
        ->assertDontSee('Inbegrepen bij het seizoensticket')
        ->assertDontSee('Koop seizoensticket')
        ->assertScript(
            "document.querySelector('#briefing-heading').getBoundingClientRect().top < document.querySelector('#tickets').getBoundingClientRect().top",
        )
        ->assertScript(
            "document.querySelector('[data-testid=\"registration-panel-status\"]').getBoundingClientRect().top < document.querySelector('#registration-heading').getBoundingClientRect().top && document.querySelector('#tickets').getBoundingClientRect().right - document.querySelector('[data-testid=\"registration-panel-status\"]').getBoundingClientRect().right < 40 && Math.abs((document.querySelector('[data-testid=\"registration-panel-status\"]').getBoundingClientRect().top + document.querySelector('[data-testid=\"registration-panel-status\"]').getBoundingClientRect().height / 2) - (document.querySelector('[data-testid=\"registration-panel-kicker\"]').getBoundingClientRect().top + document.querySelector('[data-testid=\"registration-panel-kicker\"]').getBoundingClientRect().height / 2)) < 2",
        )
        ->assertScript(
            "document.querySelector('[data-testid=\"hero-separator\"]') === null && Math.abs(document.querySelector('[data-testid=\"event-quick-facts\"]').getBoundingClientRect().top - document.querySelector('main > section').getBoundingClientRect().bottom) < 3",
        )
        ->assertAttribute(
            'a[href="https://example.com/registration"]',
            'target',
            '_blank',
        )
        ->assertAttribute(
            'a[href="https://example.com/registration"]',
            'rel',
            'noopener noreferrer',
        )
        ->assertAttribute(
            'a[href^="https://www.google.com/maps/search/"]',
            'target',
            '_blank',
        )
        ->assertAttribute(
            'a[href^="https://www.google.com/maps/search/"]',
            'rel',
            'noopener noreferrer',
        )
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->click('Bekijk seizoen')
        ->assertPathIs("/seasons/{$season->slug}")
        ->assertSee('Events in dit seizoen.')
        ->assertSee('Finale van de wintercompetitie')
        ->assertSee('Voorjaarsronde van de wintercompetitie')
        ->assertDontSee('Los ticket')
        ->assertSee('Koop seizoensticket')
        ->assertSee('Verkoop open')
        ->assertScript(
            "document.querySelector('a[href=\"/events/{$event->slug}\"]').textContent.includes('€ 15,00')",
        )
        ->assertScript(
            "document.querySelector('a[href=\"/events/{$event->slug}\"]').textContent.includes('Aanmelden mogelijk')",
        )
        ->assertScript(
            "document.querySelector('a[href=\"/events/{$springEvent->slug}\"]').textContent.includes('€ 25,00') && document.querySelector('a[href=\"/events/{$springEvent->slug}\"]').textContent.includes('Aanmelden mogelijk')",
        )
        ->assertDontSee('Sportpaleis Alkmaar, Alkmaar')
        ->assertAttribute(
            'a[href="https://example.com/season-ticket"]',
            'target',
            '_blank',
        )
        ->assertAttribute(
            'a[href="https://example.com/season-ticket"]',
            'rel',
            'noopener noreferrer',
        )
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->assertNoJavaScriptErrors();
});

test('season context without a ticket offer stays informative without sales controls', function () {
    $season = Season::factory()->create([
        'name' => 'Vrij trainingsseizoen 2027',
    ]);
    SeasonTicket::factory()->notOffered()->for($season)->create();
    $event = Event::factory()->published()->training()->for($season)->create([
        'slug' => 'vrije-training',
        'starts_at' => '2027-02-15 18:00:00',
        'price_cents' => 1750,
        'registration_opens_at' => '2027-02-01 10:00:00',
        'registration_deadline_at' => '2027-02-14 23:59:00',
        'registration_status' => EventRegistrationStatus::Closed,
        'registration_url' => null,
    ]);
    Event::factory()->published()->training()->for($season)->create([
        'title' => 'Training met verlopen inschrijving',
        'slug' => 'training-inschrijving-verlopen',
        'starts_at' => '2026-08-15 18:00:00',
        'price_cents' => 1750,
        'registration_opens_at' => '2026-06-01 10:00:00',
        'registration_deadline_at' => '2026-07-10 21:59:00',
        'registration_status' => EventRegistrationStatus::Closed,
        'registration_url' => null,
    ]);

    $page = visit("/events/{$event->slug}")
        ->on()->iPhone14Pro()
        ->withTimezone('Europe/Amsterdam');

    $page->assertSee('Vrij trainingsseizoen 2027')
        ->assertSee('Seizoen')
        ->assertSee('Nog niet geopend')
        ->assertDontSee('Aanmelding gesloten')
        ->assertSee('Inschrijving voor dit event.')
        ->assertSee('Nog niet geopend · inschrijving opent op')
        ->assertSee('Aanmelden vanaf')
        ->assertDontSee('Onderdeel van het seizoen')
        ->assertDontSee('Voor dit seizoen wordt geen seizoensticket aangeboden.')
        ->click('Bekijk seizoen')
        ->assertPathIs("/seasons/{$season->slug}")
        ->assertSee('Per event aanmelden.')
        ->assertSee('€ 17,50')
        ->assertSee('Nog niet geopend')
        ->assertSee('Inschrijving gesloten')
        ->assertDontSee('Aanmelding gesloten')
        ->assertScript(
            "document.querySelectorAll('[data-testid=\"season-event-registration-note\"]').length === 0 && [...document.querySelectorAll('[data-testid=\"season-event-registration-tooltip\"]')].every((tooltip) => getComputedStyle(tooltip).visibility === 'hidden')",
        )
        ->assertDontSee('Koop seizoensticket')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->assertNoJavaScriptErrors();

    visit("/seasons/{$season->slug}")
        ->on()->desktop()
        ->withTimezone('Europe/Amsterdam')
        ->hover(
            '#season-events li:first-child [data-testid="season-event-registration-status"]',
        )
        ->assertScript(
            "getComputedStyle(document.querySelector('#season-events li:first-child [data-testid=\"season-event-registration-tooltip\"]')).visibility === 'visible'",
        )
        ->assertNoJavaScriptErrors();
});

test('representative public pages render without browser errors', function () {
    visit([
        '/',
        '/events',
        '/projects',
        '/news',
        '/locations',
        '/about',
        '/house-rules',
        '/partners',
        '/contact',
    ])->assertNoSmoke();
});
