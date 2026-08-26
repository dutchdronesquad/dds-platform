<?php

use App\Models\Event;
use App\Support\SeoMetadata;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

test('public pages expose their canonical metadata contract', function (string $routeName, string $expectedTitle, string $canonicalPath) {
    $canonicalUrl = rtrim((string) config('app.url'), '/').$canonicalPath;
    $documentTitle = $expectedTitle === 'Dutch Drone Squad'
        ? $expectedTitle
        : "{$expectedTitle} - Dutch Drone Squad";
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
    'about' => ['about', 'Dutch Drone Squad', '/about'],
    'house rules' => ['house_rules', 'Huisregels', '/house-rules'],
    'media' => ['media', 'In de media', '/media'],
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

test('the application layout exposes DDS favicon assets', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('<link rel="icon" href="/favicon.ico?v=3" sizes="16x16 32x32 48x48">', false)
        ->assertSee('<link rel="icon" href="/favicon.svg?v=3" type="image/svg+xml" sizes="any">', false)
        ->assertSee('<link rel="apple-touch-icon" href="/apple-touch-icon.png?v=3" sizes="180x180">', false);

    $faviconDimensions = getimagesize(public_path('favicon.ico'));
    $appleTouchIconDimensions = getimagesize(public_path('apple-touch-icon.png'));
    $appleTouchIcon = imagecreatefrompng(public_path('apple-touch-icon.png'));

    assert(is_array($faviconDimensions));
    assert(is_array($appleTouchIconDimensions));

    expect($appleTouchIcon)->toBeInstanceOf(GdImage::class);
    assert($appleTouchIcon instanceof GdImage);

    $cornerColor = imagecolorat($appleTouchIcon, 0, 0);

    imagedestroy($appleTouchIcon);

    expect(hash_file('sha256', public_path('favicon.svg')))
        ->toBe(hash_file('sha256', public_path('brand/dds-logo.svg')))
        ->and($faviconDimensions)
        ->not->toBeFalse()
        ->and($faviconDimensions[0])
        ->toBe(16)
        ->and($faviconDimensions[1])
        ->toBe(16)
        ->and($appleTouchIconDimensions)
        ->not->toBeFalse()
        ->and($appleTouchIconDimensions[0])
        ->toBe(180)
        ->and($appleTouchIconDimensions[1])
        ->toBe(180)
        ->and(($cornerColor >> 24) & 0x7F)
        ->toBe(127);
});
