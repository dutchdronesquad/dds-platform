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
        ->assertSee('Alles voor je volgende persoonlijke record.')
        ->assertDontSee('Goed om te weten')
        ->assertPresent('[data-testid="pilot-development-panel"]')
        ->assertScript(
            'document.querySelector(\'[data-testid="pilot-development-heading"]\')?.querySelector(\'span\') === null',
        )
        ->assertScript(
            "(() => { const panel = document.querySelector('[data-testid=\"pilot-development-panel\"]'); const image = panel?.querySelector('img'); const benefits = panel?.querySelectorAll('dl > div'); return panel !== null && image !== null && benefits?.length === 3 && getComputedStyle(panel).backgroundColor !== 'rgba(0, 0, 0, 0)'; })()",
        )
        ->assertSee('De baan is even leeg.')
        ->assertSee('Partners & sponsors')
        ->assertDontSee('Inloggen')
        ->assertDontSee('Beheer')
        ->assertVisible('nav[aria-label="Hoofdnavigatie"]')
        ->assertMissing('button[aria-label="Open navigatie"]')
        ->assertAttribute(
            'a[aria-label="Volg Dutch Drone Squad op Instagram (opent in een nieuw tabblad)"]',
            'href',
            'https://www.instagram.com/dutchdronesquad/',
        )
        ->assertAttribute(
            'a[aria-label="Volg Dutch Drone Squad op Instagram (opent in een nieuw tabblad)"]',
            'target',
            '_blank',
        )
        ->assertAttribute(
            'a[aria-label="Volg Dutch Drone Squad op Instagram (opent in een nieuw tabblad)"]',
            'rel',
            'noopener noreferrer',
        )
        ->assertAttribute(
            'a[aria-label="Bekijk website van Droneshop.nl"]',
            'href',
            'https://droneshop.nl/',
        )
        ->assertAttribute(
            'a[aria-label="Bekijk website van Droneshop.nl"]',
            'target',
            '_blank',
        )
        ->assertAttribute(
            'a[aria-label="Bekijk website van Sportpaleis Alkmaar"]',
            'href',
            'https://sportpaleis-alkmaar.nl/',
        )
        ->assertAttribute(
            'a[aria-label="Bekijk website van Sportpaleis Alkmaar"]',
            'rel',
            'noopener noreferrer',
        )
        ->assertNotPresent('footer a[href="mailto:info@dutchdronesquad.nl"]')
        ->assertNotPresent('footer a[href="/contact?source=footer-demo"]')
        ->keys('[class~="font-sans"]', 'Tab')
        ->assertScript(
            "document.activeElement?.textContent?.trim() === 'Ga naar hoofdinhoud'",
        )
        ->assertScript(
            "(() => { const element = document.activeElement; const style = getComputedStyle(element); return element.matches(':focus-visible') && (style.outlineStyle !== 'none' || style.boxShadow !== 'none'); })()",
        )
        ->assertAttribute(
            'a[href="#main-content"]',
            'href',
            '#main-content',
        )
        ->assertAttribute('main#main-content', 'tabindex', '-1')
        ->keys('a[href="#main-content"]', 'Tab')
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
        ->assertSeeIn('#mobile-public-navigation', 'Starten met FPV')
        ->assertSeeIn(
            '#mobile-public-navigation > nav > div > p',
            'Informatie',
        )
        ->assertSeeIn('#mobile-public-navigation', 'Over DDS')
        ->assertSeeIn('#mobile-public-navigation', 'Projecten')
        ->assertSeeIn('#mobile-public-navigation', 'Partners')
        ->assertSeeIn('#mobile-public-navigation', 'Contact')
        ->assertScript(
            "document.activeElement?.getAttribute('href') === '/getting-started?source=navigation'",
        )
        ->assertScript("getComputedStyle(document.body).overflow === 'hidden'")
        ->assertScript(
            "document.querySelector('main')?.hasAttribute('inert') === true && document.querySelector('footer')?.hasAttribute('inert') === true",
        )
        ->keys(
            '#mobile-public-navigation a[href="/getting-started?source=navigation"]',
            'Escape',
        )
        ->assertMissing('#mobile-public-navigation')
        ->assertScript(
            "document.activeElement?.getAttribute('aria-label') === 'Open navigatie'",
        )
        ->assertScript("getComputedStyle(document.body).overflow !== 'hidden'")
        ->assertScript(
            "document.querySelector('main')?.hasAttribute('inert') === false && document.querySelector('footer')?.hasAttribute('inert') === false",
        )
        ->click('button[aria-label="Open navigatie"]')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->click('#mobile-public-navigation a[href="/contact"]')
        ->assertPathIs('/contact')
        ->assertMissing('#mobile-public-navigation')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->assertNoSmoke();
});

test('desktop navigation groups DDS pages in the information submenu', function () {
    $page = visit('/projects')->on()->desktop();

    $page->assertNoJavaScriptErrors()
        ->assertSeeIn(
            'nav[aria-label="Hoofdnavigatie"]',
            'Starten met FPV',
        )
        ->assertSeeIn('nav[aria-label="Hoofdnavigatie"]', 'Locaties')
        ->assertSeeIn('nav[aria-label="Hoofdnavigatie"]', 'Nieuws')
        ->assertSeeIn(
            'nav[aria-label="Hoofdnavigatie"]',
            'Informatie',
        )
        ->assertSeeIn('nav[aria-label="Hoofdnavigatie"]', 'Contact')
        ->click('button[aria-label="Open Informatie menu"]')
        ->assertAriaAttribute(
            'button[aria-label="Open Informatie menu"]',
            'expanded',
            'true',
        )
        ->assertVisible('[data-testid="information-navigation-content"]')
        ->assertSeeIn(
            '[data-testid="information-navigation-content"]',
            'Over DDS',
        )
        ->assertScript(
            'getComputedStyle(document.querySelector(\'[data-testid="information-navigation-content"]\')).backgroundColor === "rgb(23, 39, 46)"',
        )
        ->assertVisible(
            '[data-testid="information-navigation-content"] a[href="/projects"]',
        )
        ->assertVisible(
            '[data-testid="information-navigation-content"] a[href="/partners"]',
        )
        ->assertScript(
            'document.querySelector(\'[data-testid="information-navigation-content"] a[href="/about"]\').scrollWidth <= document.querySelector(\'[data-testid="information-navigation-content"] a[href="/about"]\').clientWidth',
        )
        ->assertScript(
            'document.querySelector(\'[data-testid="information-navigation-content"]\').getBoundingClientRect().width <= 164',
        )
        ->assertScript(
            'document.querySelector(\'[data-testid="information-navigation-content"] svg\') === null',
        )
        ->assertScript(
            '!document.querySelector(\'[data-testid="information-navigation-content"]\').textContent.includes("Wie we zijn en waar we voor staan.")',
        )
        ->assertScript(
            '!document.querySelector(\'[data-testid="information-navigation-content"]\').textContent.includes("Tooling en communityprojecten van DDS.")',
        )
        ->hover(
            '[data-testid="information-navigation-content"] a[href="/partners"]',
        )
        ->assertScript(
            'getComputedStyle(document.querySelector(\'[data-testid="information-navigation-content"] a[href="/partners"]\')).backgroundColor !== "rgba(0, 0, 0, 0)"',
        )
        ->assertAttribute(
            '[data-testid="information-navigation-content"] a[href="/projects"]',
            'aria-current',
            'page',
        )
        ->assertScript(
            'document.querySelectorAll(\'nav[aria-label="Hoofdnavigatie"] nav\').length === 0',
        )
        ->click(
            '[data-testid="information-navigation-content"] a[href="/partners"]',
        )
        ->assertPathIs('/partners')
        ->assertNoSmoke();
});

test('the first FPV guide explains core systems and simulator choices', function () {
    visit('/getting-started/first-fpv-flight')
        ->assertNoJavaScriptErrors()
        ->assertSee('Jij stuurt de drone')
        ->assertSee(
            'De verbinding voor besturing staat los van het videosignaal',
        )
        ->assertSee(
            'Controleer daarom of camera, videozender en goggles hetzelfde systeem gebruiken',
        )
        ->assertSee('welke regels op het event gelden')
        ->assertDontSee('welke afspraken op het event gelden')
        ->assertSee('Welke simulator kun je gebruiken?')
        ->assertSee('VelociDrone')
        ->assertSee('DDS-aanrader')
        ->assertSee('Liftoff')
        ->assertSee('DCL – The Game')
        ->assertSee('DRL Simulator')
        ->assertSee('De race director bewaakt daarbij')
        ->assertDontSee('Race control bewaakt daarbij')
        ->assertPresent('section[data-tone="air"][data-layout="split"]')
        ->assertPresent('section[data-tone="paddock"]')
        ->assertPresent('section[data-tone="warmup"][data-layout="stacked"]')
        ->assertScript(
            'getComputedStyle(document.querySelector(\'[data-testid="simulators-grid"]\')).gridTemplateColumns.split(" ").length === 4',
        )
        ->assertAttribute(
            'a[href="https://www.velocidrone.com/"]',
            'target',
            '_blank',
        )
        ->assertDontSee('Jouw besturing gaat terug')
        ->assertDontSee('video-ecosystemen')
        ->assertNoSmoke();
});

test('getting started guidance consistently calls mandatory requirements rules', function () {
    visit('/getting-started')
        ->assertNoJavaScriptErrors()
        ->assertSee('Bindende regels en event-specifieke vereisten')
        ->assertSee('Waar kunnen we je mee helpen?')
        ->assertSee('Vertel kort waar je vraag over gaat')
        ->assertPresent('section[data-tone="warmup"]')
        ->assertPresent('section[data-tone="paddock"]')
        ->assertScript(
            'getComputedStyle(document.querySelector(\'[data-testid="hero-separator"]\')).color === getComputedStyle(document.querySelector(\'section[data-tone="warmup"]\')).backgroundColor',
        )
        ->assertDontSee('Een goede vraag begint met wat context')
        ->assertDontSee('Bindende afspraken')
        ->assertNoSmoke();

    visit('/getting-started/first-dds-event')
        ->assertNoJavaScriptErrors()
        ->assertSee(
            'Tijdens een reguliere training vlieg je zelfstandig op een vast parcours',
        )
        ->assertSee('Na de opbouw loop je samen de trackwalk')
        ->assertSee('daarna vlieg je volgens de heatindeling')
        ->assertSee('De trainingsavond in zeven stappen')
        ->assertSee('Betaal je deelname')
        ->assertSee('Heb je geen seizoensticket, betaal dan na je aanmelding')
        ->assertSee('Help de baan opbouwen')
        ->assertSee('Loop de trackwalk')
        ->assertSee('Baanopbouw')
        ->assertSee('Race director')
        ->assertSee('Wacht met inschakelen tot de race director dat toestaat')
        ->assertPresent('section[data-tone="air"][data-layout="stacked"]')
        ->assertPresent('section[data-tone="paddock"]')
        ->assertScript(
            'getComputedStyle(document.querySelector(\'[data-testid="training-journey-layout"]\')).gridTemplateColumns.split(" ").length === 2',
        )
        ->assertDontSee('Aanmelden en deelnemen')
        ->assertDontSee('Event, seizoen en ticket zijn niet hetzelfde')
        ->assertDontSee('Race control')
        ->assertDontSee('Kom je ergens niet uit? Vertel via')
        ->assertMissing(
            'a[href="/contact?source=getting-started-training-help"]',
        )
        ->assertDontSee('Doe mee aan controle en briefing')
        ->assertDontSee('Volg de technische controle')
        ->assertDontSee('eventbriefing')
        ->assertDontSee('Je kunt al zelfstandig vliegen en komt voorbereid')
        ->assertDontSee('geen vrije inloop en geen eerste vlieglessen')
        ->assertDontSee('Controleer of de training past')
        ->assertDontSee(
            'Lees de locatie, tijden, deelnamevereisten en het verwachte niveau',
        )
        ->assertSee('Vlieg en laad volgens de regels')
        ->assertSee('binnen de regels vallen')
        ->assertDontSee('Vlieg en laad volgens de afspraken')
        ->assertDontSee('binnen de afspraken vallen')
        ->assertNoSmoke();
});

test('getting started guide links preserve a real entry source without inventing one', function () {
    visit('/getting-started')
        ->assertNoJavaScriptErrors()
        ->assertPresent('a[href="/getting-started/first-fpv-flight"]')
        ->assertMissing(
            'a[href="/getting-started/first-fpv-flight?source=navigation"]',
        )
        ->assertNoSmoke();

    visit('/getting-started?source=footer')
        ->assertNoJavaScriptErrors()
        ->assertPresent(
            'a[href="/getting-started/first-fpv-flight?source=footer"]',
        )
        ->assertNoSmoke();
});

test('the equipment guide does not offer personal parts list approval', function () {
    visit('/getting-started/choosing-equipment')
        ->assertNoJavaScriptErrors()
        ->assertSee('Controleer daarna welke configuraties bij de DDS-activiteiten passen')
        ->assertSee('Begin met de vraag wat voor vlieger je wilt worden')
        ->assertSee('wil je racen, freestylen of vooral recreatief vliegen?')
        ->assertSee('Van aanmelden en opbouwen tot de trackwalk')
        ->assertPresent('section[data-tone="air"][data-layout="stacked"]')
        ->assertPresent('section[data-tone="paddock"]')
        ->assertPresent('section[data-tone="warmup"]')
        ->assertDontSee('Van persoonlijke bevestiging en voorbereiding')
        ->assertDontSee('voor de activiteiten die je wilt bezoeken')
        ->assertDontSee('Vraag DDS om een onderdelenlijst te controleren')
        ->assertMissing(
            'a[href="/contact?source=getting-started-equipment-check"]',
        )
        ->assertNoSmoke();
});

test('getting started guides keep their semantic structure across themes and viewports', function () {
    visit([
        '/getting-started',
        '/getting-started/first-fpv-flight',
        '/getting-started/choosing-equipment',
        '/getting-started/first-dds-event',
    ])->assertNoAccessibilityIssues()
        ->assertNoSmoke();

    visit('/getting-started/first-fpv-flight')
        ->on()->mobile()
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->assertPresent('section[data-tone="air"]')
        ->assertPresent('section[data-tone="warmup"]')
        ->assertNoAccessibilityIssues()
        ->assertNoJavaScriptErrors();

    visit('/getting-started/choosing-equipment')
        ->on()->desktop()
        ->inDarkMode()
        ->assertScript(
            'getComputedStyle(document.querySelector(\'section[data-tone="air"]\')).backgroundColor !== getComputedStyle(document.querySelector(\'section[data-tone="paper"]\')).backgroundColor',
        )
        ->assertNoJavaScriptErrors();
});

test('beginners can find the DDS community channels', function () {
    $whatsAppCommunityUrl = 'https://chat.whatsapp.com/HInatYEIAAPEhtj3WNJy9V';
    $discordServerUrl = 'https://discord.com/invite/4eUYVrhMuk';
    $facebookGroupUrl = 'https://www.facebook.com/groups/518582471633220/';

    visit('/getting-started')
        ->assertNoJavaScriptErrors()
        ->assertSee('Ben je nieuw? Begin in een simulator')
        ->assertSee('WhatsApp-community')
        ->assertAttribute(
            sprintf('a[href="%s"]', $whatsAppCommunityUrl),
            'target',
            '_blank',
        )
        ->assertAttribute(
            sprintf('a[href="%s"]', $whatsAppCommunityUrl),
            'rel',
            'noopener noreferrer',
        )
        ->assertNoSmoke();

    visit('/contact')
        ->assertNoJavaScriptErrors()
        ->assertSeeIn('#community', 'WhatsApp-community')
        ->assertSeeIn('#community', 'Discord-server')
        ->assertSeeIn('#community', 'Facebook-groep')
        ->assertAttribute(
            'a[href="https://wa.me/31638235409"]',
            'target',
            '_blank',
        )
        ->assertAttribute(
            sprintf('a[href="%s"]', $whatsAppCommunityUrl),
            'target',
            '_blank',
        )
        ->assertAttribute(
            sprintf('a[href="%s"]', $discordServerUrl),
            'target',
            '_blank',
        )
        ->assertAttribute(
            sprintf('a[href="%s"]', $facebookGroupUrl),
            'target',
            '_blank',
        )
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
    )
        ->assertAttribute(
            'header a[href="/events"]',
            'aria-current',
            'page',
        )
        ->assertAttribute(
            'footer a[href="/events"]',
            'aria-current',
            'page',
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
        ->assertScript(
            '(document.body.innerText.match(/Aanmelden vanaf/g) ?? []).length === 1',
        )
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
        ->assertScript(
            '(document.body.innerText.match(/Aanmelden vanaf/g) ?? []).length === 1',
        )
        ->assertDontSee('Onderdeel van het seizoen')
        ->assertDontSee('Voor dit seizoen wordt geen seizoensticket aangeboden.')
        ->click('[data-testid="event-season-context"]')
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
});

test('season event registration details are available on desktop hover', function () {
    $season = Season::factory()->create();
    SeasonTicket::factory()->notOffered()->for($season)->create();
    Event::factory()->published()->training()->for($season)->create([
        'registration_status' => EventRegistrationStatus::Closed,
        'registration_url' => null,
    ]);

    visit("/seasons/{$season->slug}")
        ->on()->desktop()
        ->withTimezone('Europe/Amsterdam')
        ->assertVisible(
            '#season-events li:first-child [data-testid="season-event-registration-status"]',
        )
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
        '/getting-started',
        '/about',
        '/house-rules',
        '/partners',
        '/contact',
    ])->assertNoAccessibilityIssues()
        ->assertNoSmoke();
});

test('public page photography matches the subject of each section', function () {
    visit('/events')
        ->on()->desktop()
        ->assertAttribute(
            'main > section:first-of-type > img',
            'src',
            '/images/dds/racing/trackwalk-sportpaleis.jpg',
        );

    visit('/locations')
        ->on()->desktop()
        ->assertAttribute(
            'main > section:first-of-type > img',
            'src',
            '/images/dds/racing/sportpaleis-light-trails.jpg',
        );

    visit('/projects')
        ->on()->desktop()
        ->assertAttribute(
            'main > section:first-of-type > img',
            'src',
            '/images/dds/racing/race-control-training.jpg',
        );

    visit('/about')
        ->on()->desktop()
        ->assertAttribute(
            'section[aria-labelledby="pilots-heading"] img',
            'src',
            '/images/dds/racing/pilots-in-paddock.jpg',
        );
});

test('about page tells the DDS story and stays usable', function () {
    $mobilePage = visit('/about')->on()->iPhone14Pro();

    $mobilePage
        ->assertSee('FPV-trainingen en races')
        ->assertSee('organiseert sinds 2017 indoor FPV-trainingen')
        ->assertAttribute('main a[href="/events"]', 'href', '/events')
        ->assertAttribute(
            'main a[href="/getting-started?source=about-hero"]',
            'href',
            '/getting-started?source=about-hero',
        )
        ->assertSee('Over DDS')
        ->assertSee('Het begin')
        ->assertSee('Een plek om in de winter te vliegen.')
        ->assertSee('In 2017 zocht Klaas Schoute een indoorlocatie')
        ->assertSee('kwam hij uit bij het Sportpaleis in Alkmaar')
        ->assertSee(
            'richtte hij samen met Richard de Wit Dutch Drone Squad op',
        )
        ->assertSee('Open opgezet')
        ->assertSee('Vanaf de oprichting koos DDS voor een open opzet.')
        ->assertSee('voor een volledig seizoen gepland')
        ->assertSee('Kennismaken')
        ->assertSee('We organiseren de events op vrijwillige basis.')
        ->assertSee('reparaties aan het baanmateriaal')
        ->assertSee('investeringen in techniek')
        ->assertDontSee('meer dan een besloten groep')
        ->assertDontSee('groep vrijwilligers')
        ->assertDontSee('groep FPV-liefhebbers')
        ->assertSee('Geschiedenis · sinds 2017')
        ->assertSee('Oprichting en de eerste trainingsavond.')
        ->assertSee('In juli richten Klaas Schoute en Richard de Wit DDS op.')
        ->assertSee(
            'Eind 2017 sluit Boudewijn Pilon aan bij het organisatieteam',
        )
        ->assertSee('richt hij zich vooral op de track.')
        ->assertSee('De eerste Fly to Meat You BBQ.')
        ->assertSee('De eerste wedstrijden.')
        ->assertSee('Na een coronapauze hervat DDS de events.')
        ->assertSee('Het vijfjarig jubileum.')
        ->assertSee(
            'In juli viert DDS het vijfjarig bestaan met een vliegavond, drinken, snacks en taart.',
        )
        ->assertSee(
            'Ter gelegenheid van het jubileum is er ook een prijsvraag: hoeveel propellers zitten er in een vaas?',
        )
        ->assertSee('Zeven vliegavonden en een seizoensticket.')
        ->assertSee(
            'piloten kunnen voor het eerst één seizoensticket voor het hele seizoen kopen',
        )
        ->assertDontSee('Zef en Dennis Molenaar sluiten aan.')
        ->assertDontSee('Marijn Koesen sluit aan.')
        ->assertDontSee('vormt hij het huidige team')
        ->assertDontSee('Van startlicht tot stream')
        ->assertDontSee(
            'Voorbereiden, vliegen, terugkijken en opnieuw proberen',
        )
        ->assertSee('Piloten en bezoekers')
        ->assertSee('Deelnemen of eerst komen kijken.')
        ->assertSee('Tijdens een trainingsavond is er ruimte om vragen te stellen')
        ->assertSee(
            'Door de jaren heen hebben ook veel internationale piloten aan DDS-events deelgenomen.',
        )
        ->assertSee('Je kunt altijd eerst als bezoeker komen kijken.')
        ->assertSee('helpen de aanwezige piloten mee')
        ->assertSee('Het huidige team.')
        ->assertSee('Klaas Schoute')
        ->assertSee('Nico Kraakman')
        ->assertSee('Zef Molenaar')
        ->assertSee('Dennis Molenaar')
        ->assertSee('Marijn Koesen')
        ->assertSee('Oprichter · techniek en development')
        ->assertSee('Financiën · lessen op maat')
        ->assertSee('Team track')
        ->assertSee('Trackdesigner · team track · tijdregistratie')
        ->assertSee('Portret volgt')
        ->assertDontSee('communityplatform')
        ->assertDontSee('Meer informatie')
        ->assertDontSee('Lees verder of neem contact op.')
        ->assertScript(
            'document.querySelectorAll("main h1").length === 1 && document.querySelectorAll("main h2").length >= 4',
        )
        ->assertScript(
            '!document.querySelector("main").textContent.toLowerCase().includes("activiteit")',
        )
        ->assertScript(
            'document.querySelectorAll("[data-testid=about-timeline] time").length === 6',
        )
        ->assertScript(
            'document.querySelectorAll("[data-testid=team-portrait-placeholder]").length === 5',
        )
        ->assertAttribute('[data-testid="team-list"]', 'tabindex', '0')
        ->assertScript(
            'getComputedStyle(document.querySelector("[data-testid=team-list]")).overflowX === "auto"',
        )
        ->assertScript(
            'document.querySelector("[data-testid=team-list]").scrollWidth > document.querySelector("[data-testid=team-list]").clientWidth',
        )
        ->assertScript(
            'document.querySelector("[data-testid=team-section] a") === null',
        )
        ->assertScript(
            '[...document.querySelectorAll("footer h2, footer h3")].some((heading) => heading.textContent.trim() === "Dutch Drone Squad")',
        )
        ->assertScript(
            'document.querySelector(\'footer a[href="/about"]\')?.textContent.trim() === "Over DDS"',
        )
        ->assertScript(
            'document.querySelector("header [data-brand-variant=full]")?.textContent.includes("FPV racing community") === true',
        )
        ->assertScript(
            '[...document.querySelectorAll("[data-testid=about-timeline] time")].every((item) => item.dateTime.length === 4)',
        )
        ->assertScript(
            '[...document.querySelectorAll("main img")].every((image) => image.src.length > 0 && image.alt.trim().length > 0)',
        )
        ->assertScript(
            'document.querySelector("main img").complete && document.querySelector("main img").naturalWidth > 0',
        )
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->assertScript(
            '[...document.querySelectorAll("main a[href]")].every((link) => link.tabIndex >= 0)',
        )
        ->assertNoAccessibilityIssues()
        ->assertNoSmoke();

    visit('/about')
        ->on()->desktop()
        ->assertScript(
            'getComputedStyle(document.querySelector("[data-testid=team-list]")).display === "grid"',
        )
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->assertNoAccessibilityIssues()
        ->assertNoJavaScriptErrors();
});

test('partner catalogue stays aligned and accessible on mobile', function () {
    $page = visit('/partners')->on()->iPhone14Pro();

    $page->assertSee('Samen maken we meer mogelijk.')
        ->assertSee('onze partners helpen Dutch Drone Squad')
        ->assertSee('Bekijk hun bijdrage')
        ->assertSee('Partners die onze races mogelijk maken.')
        ->assertSee('van een vaste indoorlocatie tot gesponsord trackmateriaal')
        ->assertAttribute('a[href="#partners"]', 'href', '#partners')
        ->assertScript(
            'document.querySelectorAll("#partners-heading > span").length',
            2,
        )
        ->assertScript(
            '[...document.querySelectorAll("#partners-heading > span")].every((line) => line.scrollWidth <= line.clientWidth)',
        )
        ->assertSee('Droneshop.nl')
        ->assertSee('Sportpaleis Alkmaar')
        ->assertDontSee('Partner 01')
        ->assertVisible('img[alt="Droneshop.nl"]')
        ->assertVisible('img[alt="Logo van Sportpaleis Alkmaar"]')
        ->assertAttribute(
            'a[aria-label="Bezoek de website van Sportpaleis Alkmaar"]',
            'target',
            '_blank',
        )
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->assertScript(
            '[...document.querySelectorAll("#partners img")].every((image) => image.complete && image.naturalWidth > 0 && image.naturalHeight > 0)',
        )
        ->assertScript(
            '[...document.querySelectorAll("#partners a[target=_blank]")].every((link) => link.rel.includes("noopener") && link.rel.includes("noreferrer"))',
        )
        ->assertNoSmoke();
});

test('project catalogue stays usable on mobile', function () {
    $page = visit('/projects')
        ->on()->iPhone14Pro();

    $page->assertSee('Projecten uit de praktijk.')
        ->assertSee('Baanontwerp')
        ->assertSee('Een baanidee wordt pas echt goed als je het kunt zien, testen en delen.')
        ->assertSee('Ontwerpen, timen en livestreamen.')
        ->assertSee('TrackDraw')
        ->assertSee('Race Voice')
        ->assertSee('YouTube Chapters')
        ->assertSee('Timer Dotfiles')
        ->assertSee('Bijdragen aan RotorHazard')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->assertScript(
            '[...document.querySelectorAll("main a[href]")].every((link) => link.tabIndex >= 0)',
        )
        ->assertScript(
            '[...document.querySelectorAll("a[target=_blank]")].every((link) => link.rel.includes("noopener") && link.rel.includes("noreferrer"))',
        )
        ->assertVisible(
            '[data-testid="project-spotlight-container"] [data-testid="project-external-link-trackdraw"]',
        )
        ->assertVisible('[data-testid="project-spotlight-video-trackdraw"]')
        ->assertNoAccessibilityIssues()
        ->assertNoJavaScriptErrors();
});

test('software and hardware projects stay balanced on desktop', function () {
    $page = visit('/projects')
        ->on()->desktop();

    $page->assertSee('Stream Overlays')
        ->assertSee('Baanontwerp')
        ->assertSee('Van racecontrol tot baanopbouw.')
        ->assertSee('De projecten op deze pagina ontstonden vanuit wat we tijdens races, trainingen en livestreams nodig hadden. De ene keer was dat software, de andere keer hardware of een bijdrage aan een bestaand open-sourceproject.')
        ->assertSee('Ontwerpen, timen en livestreamen.')
        ->assertSee('Van TrackDraw en RotorHazard-plugins tot flightcases voor timing en livestreams.')
        ->assertSee('Open de editor')
        ->assertSee('Bekijk de overlays')
        ->assertSee('Download de laatste release')
        ->assertSee('Bekijk de plugin op GitHub')
        ->assertSee('Live-feedkoffer')
        ->assertSee('Event-livestreamkoffer')
        ->assertSee('Tijdregistratiekoffer')
        ->assertSee('Timer Dotfiles')
        ->assertSee('Bijdragen aan RotorHazard')
        ->assertSee('Alle projecten')
        ->assertSee('Benieuwd wat er achter onze races draait?')
        ->assertScript(
            'document.querySelector("main > section")?.getBoundingClientRect().height >= 700',
        )
        ->assertScript(
            'document.querySelectorAll("[data-testid=project-spotlight-trackdraw]").length === 1',
        )
        ->assertScript(
            'document.querySelector("[data-testid=project-spotlight-container]")?.getBoundingClientRect().width <= 1280',
        )
        ->assertScript(
            'getComputedStyle(document.querySelector("[data-testid=project-spotlight-media-frame]")).borderTopWidth === "1px"',
        )
        ->assertScript(
            'document.querySelectorAll("[data-testid^=project-card-]").length === 9',
        )
        ->assertScript(
            'document.querySelectorAll("[data-testid=project-card-trackdraw]").length === 1',
        )
        ->assertScript(
            'document.querySelector("[data-testid=project-media-image-trackdraw]")?.getAttribute("src") === "/images/projects/trackdraw-mark-light.svg"',
        )
        ->assertScript(
            'document.querySelector("[data-testid=project-spotlight-video-trackdraw]")?.getAttribute("poster") === "/images/projects/trackdraw-editor.webp"',
        )
        ->assertScript(
            'document.querySelector("[data-testid=project-external-link-live-feed-flightcase]") === null',
        )
        ->assertScript(
            'document.querySelector("[aria-label=\"Filter projecten op type\"]")?.getAttribute("role") === "group"',
        )
        ->assertScript(
            '[...document.querySelectorAll("[aria-controls=projects-grid-results]")].every((button) => button.tagName === "BUTTON")',
        )
        ->assertScript(
            'getComputedStyle(document.querySelector("[data-testid=projects-community-band]")).backgroundColor === "rgb(243, 146, 0)"',
        )
        ->assertScript(
            'getComputedStyle(document.querySelector("[data-testid=project-media-rh-race-voice]")).backgroundColor !== getComputedStyle(document.querySelector("section[aria-labelledby=projects-grid-heading]")).backgroundColor',
        )
        ->assertScript(
            '[...document.querySelectorAll("[data-testid=page-eyebrow]")].every((element) => ["none", "\\"\\""].includes(getComputedStyle(element, "::before").content))',
        )
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->assertNoAccessibilityIssues()
        ->assertNoSmoke();
});

test('the TrackDraw card uses its dark logo variant in dark mode', function () {
    $page = visit('/projects')
        ->on()->desktop()
        ->inDarkMode();

    $page->assertVisible('[data-testid="project-media-dark-image-trackdraw"]')
        ->assertScript(
            'document.querySelector("[data-testid=project-media-dark-image-trackdraw]")?.getAttribute("src") === "/images/projects/trackdraw-mark-dark.svg"',
        )
        ->assertScript(
            'getComputedStyle(document.querySelector("[data-testid=project-media-image-trackdraw]")).display === "none"',
        )
        ->assertNoJavaScriptErrors();
});

test('the project grid can be filtered by type', function () {
    $page = visit('/projects')
        ->on()->desktop();

    $page->assertScript(
        'document.querySelector("[data-testid=project-card-rh-race-voice]") !== null',
    )
        ->assertScript(
            'document.querySelector("[data-testid=project-card-live-feed-flightcase]") !== null',
        )
        ->click('Flightcases')
        ->assertScript(
            'document.querySelector("[data-testid=project-card-rh-race-voice]") === null',
        )
        ->assertScript(
            'document.querySelector("[data-testid=project-card-live-feed-flightcase]") !== null',
        )
        ->assertScript(
            'document.querySelector("button[aria-pressed=true]")?.textContent?.trim() === "Flightcases"',
        )
        ->assertScript(
            'document.querySelectorAll("#projects-grid-results > article").length === 3',
        )
        ->assertScript(
            'document.querySelector("[data-testid=projects-grid-status]")?.textContent?.replace(/\\s+/g, " ").trim() === "3 projecten zichtbaar"',
        )
        ->click(
            'button[aria-controls="projects-grid-results"]:first-child',
        )
        ->assertScript(
            'document.querySelector("[data-testid=project-card-rh-race-voice]") !== null',
        )
        ->assertScript(
            'document.querySelector("[data-testid=project-card-live-feed-flightcase]") !== null',
        )
        ->click('RotorHazard')
        ->assertScript(
            'document.querySelector("[data-testid=project-card-rotorhazard-contributions]") !== null',
        )
        ->assertScript(
            'document.querySelector("[data-testid=project-card-rh-race-voice]") !== null',
        )
        ->assertScript(
            'document.querySelector("[data-testid=project-card-live-feed-flightcase]") === null',
        )
        ->assertScript(
            'document.querySelector("[data-testid=projects-grid-status]")?.textContent?.replace(/\\s+/g, " ").trim() === "5 projecten zichtbaar"',
        )
        ->assertScript(
            'document.querySelectorAll("#projects-grid-results > article").length === 5',
        )
        ->click(
            'button[aria-controls="projects-grid-results"]:first-child',
        )
        ->assertScript(
            'document.querySelector("[data-testid=project-card-rh-race-voice]") !== null',
        )
        ->assertScript(
            'document.querySelectorAll("#projects-grid-results > article").length === 9',
        )
        ->assertNoJavaScriptErrors();
});
