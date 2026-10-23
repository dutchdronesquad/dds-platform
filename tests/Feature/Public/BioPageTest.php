<?php

use App\Models\Article;
use App\Models\Event;
use App\Support\BioPageData;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(new DateTimeImmutable('2026-10-23T10:00:00+00:00'));
});

test('bio is public with canonical metadata and useful links when feeds are empty', function () {
    $this->get(route('bio'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('public/bio')
        ->where('featured', null)->where('news', [])->where('events', [])
        ->has('links', 4)->has('socials', 4)
        ->where('links.0.href', '/events')
        ->where('links.2.href', '/contact#community')
        ->where('seo.canonicalUrl', rtrim((string) config('app.url'), '/').'/bio')
        ->where('seo.openGraph.type', 'website')
    );
});

test('bio selects only visible content in publication and event order with image fallbacks', function () {
    Article::factory()->create();
    Article::factory()->archived()->create();
    Article::factory()->published()->create(['published_at' => now()->addDay()]);
    Article::factory()->published()->create(['published_at' => null]);
    Article::factory()->published()->create(['title' => 'Ouder bericht', 'published_at' => now()->subDays(2)]);
    Article::factory()->published()->create(['title' => 'Nieuwste bericht', 'published_at' => now()->subDay()]);
    Event::factory()->create();
    Event::factory()->published()->create(['published_at' => now()->addDay()]);
    Event::factory()->published()->create(['starts_at' => now()->subDay()]);
    Event::factory()->published()->create(['title' => 'Later event', 'starts_at' => now()->addDays(3)]);
    Event::factory()->published()->create(['title' => 'Eerstvolgend event', 'starts_at' => now()->addDay()]);
    Event::factory()->published()->cancelled()->create(['title' => 'Geannuleerd event', 'starts_at' => now()->addDays(2)]);

    $this->get(route('bio'))->assertInertia(fn (Assert $page) => $page
        ->where('featured.title', 'Nieuwste bericht')
        ->where('featured.image.src', '/images/dds/racing/pilot-at-training.jpg')
        ->has('news', 1)->where('news.0.title', 'Ouder bericht')
        ->has('events', 3)->where('events.0.title', 'Eerstvolgend event')
        ->where('events.0.image.src', '/images/dds/racing/indoor-track.jpg')
        ->where('events.1.note', 'Geannuleerd · zondag 25 oktober 2026')
        ->where('events.2.title', 'Later event')
    );
});

test('bio promotes an event when no news is available', function () {
    $event = Event::factory()->published()->create(['starts_at' => now()->addDay()]);
    $this->get(route('bio'))->assertInertia(fn (Assert $page) => $page
        ->where('featured.title', $event->title)->where('events', [])
    );
});

test('a manual feature wins before deduplication across all sections', function () {
    $article = Article::factory()->published()->create(['slug' => 'seizoen']);
    config()->set('bio.featured', ['title' => 'Campagne', 'href' => route('news.show', $article->slug), 'note' => 'Schrijf je in']);
    config()->set('bio.links', [
        ['title' => 'Dubbel', 'href' => '/news/seizoen/'],
        ['title' => 'Eerst', 'href' => '/events'],
        ['title' => 'Tweede', 'href' => '/contact'],
    ]);
    config()->set('bio.socials', [['title' => 'Ook dubbel', 'href' => '/events']]);
    $this->get(route('bio'))->assertInertia(fn (Assert $page) => $page
        ->where('featured.title', 'Campagne')->where('featured.image', null)
        ->where('news', [])->has('links', 2)->where('links.0.title', 'Eerst')
        ->where('links.1.title', 'Tweede')->where('socials', [])
    );
});

test('temporary items respect inclusive starts and exclusive ends with explicit offsets', function (string $now, bool $visible) {
    $this->travelTo(new DateTimeImmutable($now));
    $item = ['title' => 'Tijdelijk', 'href' => '/events', 'starts_at' => '2026-10-25T02:30:00+02:00', 'ends_at' => '2026-10-25T02:30:00+01:00'];
    config()->set('bio.featured', $item);
    config()->set('bio.links', [array_replace($item, ['href' => '/contact'])]);
    config()->set('bio.socials', [array_replace($item, ['href' => 'https://example.com'])]);
    $data = app(BioPageData::class)->forPage();
    expect($data['featured']['title'] ?? null)->toBe($visible ? 'Tijdelijk' : null)
        ->and($data['links'])->toHaveCount($visible ? 1 : 0)
        ->and($data['socials'])->toHaveCount($visible ? 1 : 0);
})->with([
    'before start' => ['2026-10-25T00:29:59+00:00', false],
    'exact start' => ['2026-10-25T00:30:00+00:00', true],
    'before end' => ['2026-10-25T01:29:59+00:00', true],
    'exact end' => ['2026-10-25T01:30:00+00:00', false],
]);

test('an inactive feature falls back to news and expired links disappear', function () {
    Article::factory()->published()->create(['title' => 'Automatisch']);
    config()->set('bio.featured', ['title' => 'Later', 'href' => '/later', 'starts_at' => '2026-11-01T00:00:00+01:00']);
    config()->set('bio.links', [
        ['title' => 'Verlopen', 'href' => '/oud', 'ends_at' => '2026-10-23T12:00:00+02:00'],
        ['title' => 'Actief', 'href' => '/actief', 'image' => ['src' => '/images/dds/racing/homepage-hero.jpg', 'alt' => 'Indoorbaan']],
    ]);
    $this->get(route('bio'))->assertInertia(fn (Assert $page) => $page
        ->where('featured.title', 'Automatisch')->has('links', 1)
        ->where('links.0.title', 'Actief')->has('links.0.image.src')
    );
});

test('bio rejects invalid configuration before it can render', function (array $overrides) {
    config()->set('bio.links', [array_replace(['title' => 'Test', 'href' => '/events'], $overrides)]);
    expect(fn () => app(BioPageData::class)->forPage())->toThrow(InvalidArgumentException::class);
})->with([
    'javascript URL' => [['href' => 'javascript:alert(1)']],
    'protocol relative URL' => [['href' => '//evil.example']],
    'backslash URL' => [['href' => '/\\evil.example']],
    'control character' => [['href' => "/\nevil.example"]],
    'credential URL' => [['href' => 'https://name:password@example.com']],
    'empty title' => [['title' => '']],
    'bad image' => [['image' => ['src' => 'javascript:alert(1)', 'alt' => 'Art']]],
    'missing alt' => [['image' => ['src' => '/art.jpg']]],
    'missing timezone' => [['starts_at' => '2026-10-23T12:00:00']],
    'impossible date' => [['starts_at' => '2026-02-30T12:00:00+01:00']],
    'reversed window' => [['starts_at' => '2026-10-24T12:00:00+02:00', 'ends_at' => '2026-10-23T12:00:00+02:00']],
    'empty window' => [['starts_at' => '2026-10-23T12:00:00+02:00', 'ends_at' => '2026-10-23T12:00:00+02:00']],
    'unknown field' => [['secret' => 'not public']],
]);

test('bio requires configured lists', function () {
    config()->set('bio.links', ['title' => 'Not a list']);
    expect(fn () => app(BioPageData::class)->forPage())->toThrow(InvalidArgumentException::class);
});
