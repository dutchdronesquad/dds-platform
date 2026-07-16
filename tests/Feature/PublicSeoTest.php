<?php

use App\Models\Event;
use App\Support\SeoMetadata;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

test('public pages expose their canonical metadata contract', function (string $routeName, string $expectedTitle, string $canonicalPath) {
    $canonicalUrl = rtrim((string) config('app.url'), '/').$canonicalPath;
    $documentTitle = "{$expectedTitle} - Dutch Drone Squad";
    $response = $this->get(route($routeName));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('seo.title', $expectedTitle)
            ->where('seo.canonicalUrl', $canonicalUrl)
            ->where('seo.robots', 'index, follow')
            ->where('seo.openGraph.title', $documentTitle)
            ->where('seo.openGraph.url', $canonicalUrl)
            ->where('seo.openGraph.type', 'website')
            ->where('seo.openGraph.siteName', 'Dutch Drone Squad')
            ->where('seo.description', fn (mixed $description): bool => is_string($description) && $description !== '')
            ->has('seo.openGraph.image')
            ->has('seo.openGraph.imageAlt'),
        )
        ->assertSee('<title', false)
        ->assertSee($documentTitle)
        ->assertSee('name="description"', false)
        ->assertSee('rel="canonical"', false)
        ->assertSee('property="og:image"', false);
})->with([
    'home' => ['home', 'Indoor FPV-racing in Alkmaar', '/'],
    'events' => ['events.index', 'Agenda', '/events'],
    'projects' => ['projects.index', 'Projecten', '/projects'],
    'news' => ['news.index', 'Nieuws', '/news'],
    'locations' => ['locations.index', 'Locaties', '/locations'],
    'about' => ['about', 'Over DDS', '/about'],
    'house rules' => ['house_rules', 'Huisregels', '/house-rules'],
    'partners' => ['partners', 'Partners', '/partners'],
    'contact' => ['contact', 'Contact', '/contact'],
]);

test('event detail metadata uses the event title and stable public url', function () {
    $event = Event::factory()->published()->create([
        'title' => 'Winter Training',
        'slug' => 'winter-training',
    ]);

    $this->get(route('events.show', ['event' => $event->slug]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('seo.title', 'Winter Training')
            ->where('seo.canonicalUrl', rtrim((string) config('app.url'), '/').'/events/winter-training')
            ->where('seo.openGraph.url', rtrim((string) config('app.url'), '/').'/events/winter-training')
            ->where('seo.openGraph.type', 'website'),
        );
});

test('unknown page metadata falls back to the DDS defaults', function () {
    $metadata = (new SeoMetadata)->forPage('missing-page');

    expect($metadata)
        ->title->toBe('Dutch Drone Squad')
        ->description->toBe('Dutch Drone Squad brengt FPV-piloten, makers en partners samen rond indoor drone racing in Alkmaar.')
        ->canonicalUrl->toBe(rtrim((string) config('app.url'), '/').'/')
        ->robots->toBe('index, follow')
        ->openGraph->image->toBe(rtrim((string) config('app.url'), '/').'/images/dds/racing/homepage-hero.jpg');
});
