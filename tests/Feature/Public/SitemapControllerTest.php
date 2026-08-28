<?php

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\Event;
use App\Models\Location;
use App\Models\Season;
use Illuminate\Support\Facades\URL;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;

beforeEach(function () {
    config()->set('app.url', 'https://dutchdronesquad.nl');
    URL::forceRootUrl('https://dutchdronesquad.nl');
    $this->travelTo('2026-08-28 12:00:00');
});

test('the public sitemap contains fixed pages and getting started guides', function () {
    $response = $this->get(route('sitemap'));

    $response
        ->assertOk()
        ->assertHeaderContains('Content-Type', 'text/xml');

    expect(sitemapLocations(responseContent($response)))
        ->toContain(
            'https://dutchdronesquad.nl',
            'https://dutchdronesquad.nl/events',
            'https://dutchdronesquad.nl/projects',
            'https://dutchdronesquad.nl/news',
            'https://dutchdronesquad.nl/locations',
            'https://dutchdronesquad.nl/getting-started',
            'https://dutchdronesquad.nl/getting-started/first-fpv-flight',
            'https://dutchdronesquad.nl/getting-started/choosing-equipment',
            'https://dutchdronesquad.nl/getting-started/first-dds-event',
            'https://dutchdronesquad.nl/about',
            'https://dutchdronesquad.nl/house-rules',
            'https://dutchdronesquad.nl/media',
            'https://dutchdronesquad.nl/partners',
            'https://dutchdronesquad.nl/contact',
        )
        ->not->toContain(
            'https://dutchdronesquad.nl/dashboard',
            'https://dutchdronesquad.nl/login',
        );
});

test('the public sitemap only contains content that visitors can open', function () {
    $visibleArticle = Article::factory()->published()->create([
        'slug' => 'published-article',
        'updated_at' => '2026-08-20 10:00:00',
    ]);
    Article::factory()->create(['slug' => 'draft-article']);
    Article::factory()->create([
        'slug' => 'future-article',
        'status' => ArticleStatus::Published,
        'published_at' => now()->addDay(),
    ]);

    $location = Location::factory()->create([
        'slug' => 'sportpaleis-alkmaar',
        'updated_at' => '2026-08-21 11:00:00',
    ]);
    $visibleSeason = Season::factory()->create(['name' => 'Seizoen 2026']);
    $emptySeason = Season::factory()->create(['name' => 'Seizoen 2027']);
    $visibleEvent = Event::factory()
        ->for($location)
        ->for($visibleSeason)
        ->published()
        ->create([
            'slug' => 'open-training',
            'updated_at' => '2026-08-22 12:00:00',
        ]);
    $cancelledEvent = Event::factory()
        ->for($location)
        ->published()
        ->cancelled()
        ->create(['slug' => 'cancelled-training']);
    Event::factory()->for($location)->create(['slug' => 'draft-event']);
    Event::factory()->for($location)->published()->create([
        'slug' => 'future-event',
        'published_at' => now()->addDay(),
    ]);

    $response = $this->get(route('sitemap'));
    $locations = sitemapLocations(responseContent($response));

    expect($locations)
        ->toContain(
            route('news.show', $visibleArticle),
            route('events.show', $visibleEvent),
            route('events.show', $cancelledEvent),
            route('locations.show', $location),
            route('seasons.show', $visibleSeason),
        )
        ->not->toContain(
            'https://dutchdronesquad.nl/news/draft-article',
            'https://dutchdronesquad.nl/news/future-article',
            'https://dutchdronesquad.nl/events/draft-event',
            'https://dutchdronesquad.nl/events/future-event',
            route('seasons.show', $emptySeason),
        )
        ->and(sitemapLastModifications(responseContent($response)))->toMatchArray([
            route('news.show', $visibleArticle) => '2026-08-20T10:00:00+00:00',
            route('locations.show', $location) => '2026-08-21T11:00:00+00:00',
            route('events.show', $visibleEvent) => '2026-08-22T12:00:00+00:00',
        ]);
});

test('robots txt advertises the public sitemap', function () {
    $robots = file_get_contents(public_path('robots.txt'));

    expect($robots)->toContain('Sitemap: https://dutchdronesquad.nl/sitemap.xml');
});

/** @param TestResponse<Response> $response */
function responseContent(TestResponse $response): string
{
    $content = $response->getContent();

    if (! is_string($content)) {
        throw new UnexpectedValueException('Expected a string response body.');
    }

    return $content;
}

/**
 * @return list<string>
 */
function sitemapLocations(string $xml): array
{
    $sitemap = new SimpleXMLElement($xml);

    return array_map(
        static fn (SimpleXMLElement $url): string => (string) $url->loc,
        iterator_to_array($sitemap->url, false),
    );
}

/**
 * @return array<string, string>
 */
function sitemapLastModifications(string $xml): array
{
    $sitemap = new SimpleXMLElement($xml);

    $lastModifications = [];

    foreach ($sitemap->url as $url) {
        if ((string) $url->lastmod !== '') {
            $lastModifications[(string) $url->loc] = (string) $url->lastmod;
        }
    }

    return $lastModifications;
}
