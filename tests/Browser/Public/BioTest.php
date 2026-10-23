<?php

use App\Models\Event;
use Illuminate\Support\Facades\Vite;

beforeEach(function () {
    Vite::useHotFile(storage_path('framework/testing/vite.hot'));
    $this->travelTo(new DateTimeImmutable('2026-10-23T10:00:00+00:00'));
});

test('bio visitors see accessible standalone content and can open the agenda', function (int $width, bool $dark) {
    config()->set('bio.featured', [
        'title' => 'Het indoorseizoen gaat weer van start',
        'href' => '/news',
        'note' => '23 oktober 2026',
        'image' => ['src' => '/images/dds/racing/homepage-hero.jpg', 'alt' => 'Indoorbaan van DDS'],
    ]);
    Event::factory()->published()->create([
        'title' => 'Indoor vliegen in Alkmaar',
        'starts_at' => '2026-11-01 17:00:00',
    ]);
    $page = visit('/bio');
    if ($dark) {
        $page->inDarkMode();
    }
    $page->resize($width, 844)->assertSee('Waar dronepiloten samenkomen.')
        ->assertSee('Het indoorseizoen gaat weer van start')
        ->assertSee('Indoor vliegen in Alkmaar')
        ->assertMissing('nav[aria-label="Hoofdnavigatie"]')
        ->assertMissing('footer')
        ->keys('[class~="font-sans"]', 'Tab')
        ->assertScript('document.activeElement?.getAttribute("aria-label") === "Dutch Drone Squad home"')
        ->assertNoJavaScriptErrors()
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->assertNoAccessibilityIssues();
    $page->click('Bekijk de agenda')->assertPathIs('/events')->assertSee('Agenda')->assertNoJavaScriptErrors();
})->with([
    'narrow mobile' => [320, false],
    'mobile' => [390, false],
    'dark mobile' => [390, true],
    'desktop' => [1280, false],
]);

test('bio hides empty sections and remains usable without automatic content', function () {
    visit('/bio')->resize(390, 844)
        ->assertSee('Bekijk de agenda')
        ->assertMissing('section[aria-label="Meer nieuws"]')
        ->assertMissing('section[aria-label="Komende evenementen"]')
        ->assertNoJavaScriptErrors();
});

test('bio images that fail do not leave a broken image in the layout', function () {
    config()->set('bio.featured', [
        'title' => 'Campagne zonder foto',
        'href' => '/news',
        'image' => ['src' => '/images/nonexistent-bio.jpg', 'alt' => 'Campagne'],
    ]);
    visit('/bio')->resize(390, 844)
        ->assertSee('Campagne zonder foto')
        ->assertScript('document.querySelector("img[src=\"/images/nonexistent-bio.jpg\"]")?.hidden === true')
        ->assertNoJavaScriptErrors();
});
