<?php

use App\Support\SeoMetadata;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

test('public pages expose complete metadata with canonical application urls', function (string $routeName, string $pageKey, string $canonicalPath) {
    $metadata = (new SeoMetadata)->forPage($pageKey);
    $documentTitle = $metadata['title'] === $metadata['openGraph']['siteName']
        ? $metadata['openGraph']['siteName']
        : "{$metadata['title']} - {$metadata['openGraph']['siteName']}";

    $response = $this->get(route($routeName));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('seo', $metadata)
            ->where('seo.canonicalUrl', rtrim((string) config('app.url'), '/').$canonicalPath)
            ->has('seo.title')
            ->has('seo.description')
            ->has('seo.robots')
            ->has('seo.openGraph.image')
            ->has('seo.openGraph.imageAlt'),
        )
        ->assertSee('<title', false)
        ->assertSee($documentTitle)
        ->assertSee('name="description"', false)
        ->assertSee('rel="canonical"', false)
        ->assertSee('property="og:image"', false);
})->with([
    'home' => ['home', 'home', '/'],
    'events' => ['events.index', 'events', '/events'],
    'projects' => ['projects.index', 'projects', '/projects'],
    'news' => ['news.index', 'news', '/news'],
    'locations' => ['locations.index', 'locations', '/locations'],
    'about' => ['about', 'about', '/about'],
    'house rules' => ['house_rules', 'house_rules', '/house-rules'],
    'partners' => ['partners', 'partners', '/partners'],
    'contact' => ['contact', 'contact', '/contact'],
]);

test('event detail metadata uses the event title and stable public url', function () {
    $this->get(route('events.show', ['slug' => 'winter-training']))
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
        ->description->toBe(config('seo.defaults.description'))
        ->canonicalUrl->toBe(rtrim((string) config('app.url'), '/').'/')
        ->robots->toBe('index, follow')
        ->openGraph->image->toBe(rtrim((string) config('app.url'), '/').'/images/dds/racing/homepage-hero.jpg');
});
