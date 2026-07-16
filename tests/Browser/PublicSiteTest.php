<?php

use App\Enums\EventRegistrationStatus;
use App\Enums\EventType;
use App\Models\Event;
use App\Models\Location;
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
    Event::factory()->published()->training()->create([
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
        ->assertSee('Geannuleerde race')
        ->assertSee('Aanmelden mogelijk')
        ->assertSee('Vol')
        ->assertSee('Wachtlijst')
        ->assertSee('Dit event gaat niet door')
        ->click('Trainingen')
        ->assertQueryStringHas('type', 'training')
        ->assertAriaAttribute(
            'nav[aria-label="Filter events op type"] a[href="/events?type=training"]',
            'current',
            'page',
        )
        ->assertSee('Open training')
        ->assertDontSee('Volle race')
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
    $event = Event::factory()->for($location)->published()->training()->create([
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

    $page = visit("/events/{$event->slug}")
        ->on()->iPhone14Pro()
        ->withTimezone('Europe/Amsterdam');

    $page->assertSee('Lange indoor briefing')
        ->assertSee('donderdag 15 oktober 2026')
        ->assertSee('Start van de briefing.')
        ->assertSee('Einde van de briefing.')
        ->assertSee('Aanmelden mogelijk')
        ->assertSee('16 plekken totaal')
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
