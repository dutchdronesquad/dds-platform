<?php

use App\Models\Event;
use App\Models\Redirect;
use App\Support\GettingStartedGuides;
use Inertia\Testing\AssertableInertia as Assert;

test('the hub index lists every guide in order with seo metadata and no source by default', function () {
    $guides = GettingStartedGuides::fromConfig()->all();

    $this->get(route('getting_started.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/getting-started/index')
            ->where('entrySource', null)
            ->missing('firstEvent')
            ->missing('currentSeason')
            ->has('guides', count($guides))
            ->where('guides.0.slug', $guides[0]->slug)
            ->where('guides.0.editorialOwner', $guides[0]->editorialOwner)
            ->where('guides.0.reviewedAt', $guides[0]->reviewedAt)
            ->where('guides.0.heroImage.src', '/images/dds/racing/drone-in-flight.jpg')
            ->where('guides.1.heroImage.src', '/images/dds/racing/controller-close-up.jpg')
            ->where('guides.2.heroImage.src', '/images/dds/racing/pilots-with-goggles.jpg')
            ->where(
                'guides.2.summary',
                'Van aanmelden en opbouwen tot de trackwalk, heats, veilig laden en samen opruimen: zo verloopt een trainingsavond.',
            )
            ->where('seo.title', 'Beginnen met FPV')
            ->where('seo.canonicalUrl', rtrim(config('app.url'), '/').'/getting-started'),
        );
});

test('the hub index sanitizes the entry source query parameter', function (string $source) {
    $this->get(route('getting_started.index', ['source' => $source]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/getting-started/index')
            ->where('entrySource', $source),
        );
})->with([
    'navigation',
    'homepage',
    'event',
    'location',
    'contact',
    'footer',
    'search',
]);

test('an unrecognized entry source is discarded on the hub index', function () {
    $this->get(route('getting_started.index', ['source' => 'not-a-real-source']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/getting-started/index')
            ->where('entrySource', null),
        );
});

test('a malformed entry source is discarded instead of causing an error', function () {
    $this->get(route('getting_started.index', ['source' => ['navigation']]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/getting-started/index')
            ->where('entrySource', null),
        );
});

test('each guide detail page renders its own component with matching seo metadata', function (string $slug) {
    $guide = GettingStartedGuides::fromConfig()->find($slug);

    $this->get(route('getting_started.show', ['guide' => $slug]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component("public/getting-started/{$slug}")
            ->where('guide.slug', $slug)
            ->where('guide.editorialOwner', $guide->editorialOwner)
            ->where('guide.reviewedAt', $guide->reviewedAt)
            ->where('seo.title', $guide->title)
            ->where('seo.description', $guide->summary)
            ->where('seo.openGraph.imageAlt', $guide->heroImage['alt'])
            ->where('seo.canonicalUrl', rtrim(config('app.url'), '/')."/getting-started/{$slug}"),
        );
})->with([
    'first-fpv-flight',
    'choosing-equipment',
    'first-dds-event',
]);

test('an unknown guide slug 404s', function () {
    $this->get('/getting-started/not-a-real-guide')
        ->assertNotFound();
});

test('guide detail pages do not carry live event or season data', function (string $slug) {
    Event::factory()->training()->published()->create([
        'starts_at' => now()->addWeek(),
    ]);

    $this->get(route('getting_started.show', ['guide' => $slug]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component("public/getting-started/{$slug}")
            ->missing('firstEvent')
            ->missing('currentSeason'),
        );
})->with([
    'first-fpv-flight',
    'choosing-equipment',
    'first-dds-event',
]);

test('the guide catalogue has unique ordered entries with matching React pages and review metadata', function () {
    $guides = GettingStartedGuides::fromConfig()->all();
    $slugs = array_map(
        fn ($guide): string => $guide->slug,
        $guides,
    );
    $sortOrders = array_map(
        fn ($guide): int => $guide->sortOrder,
        $guides,
    );

    expect($slugs)
        ->toHaveSameSize(array_unique($slugs))
        ->and($sortOrders)->toBe(array_values(array_unique($sortOrders)));

    foreach ($guides as $guide) {
        expect(resource_path("js/pages/public/getting-started/{$guide->slug}.tsx"))->toBeFile()
            ->and($guide->editorialOwner)->not->toBeEmpty()
            ->and($guide->reviewedAt)->toMatch('/^\d{4}-\d{2}-\d{2}$/');
    }
});

test('legacy trainingen visitors still redirect to the training agenda', function () {
    Redirect::query()->create([
        'source_path' => '/trainingen',
        'target_url' => '/events?type=training',
        'status_code' => 301,
        'is_active' => true,
    ]);

    $this->get('/trainingen')
        ->assertRedirect('/events?type=training');
});
