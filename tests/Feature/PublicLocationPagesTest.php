<?php

use App\Enums\EventStatus;
use App\Enums\LocationEnvironment;
use App\Models\Event;
use App\Models\Location;
use App\Models\MediaAsset;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

test('the location index lists all locations with a localized excerpt', function () {
    $alkmaar = Location::factory()->create([
        'name' => 'Sportpaleis Alkmaar',
        'city' => 'Alkmaar',
        'description' => [
            'en' => 'An indoor venue for FPV drone racing.',
            'nl' => 'Een binnenlocatie voor FPV-droneraces.',
        ],
    ]);
    $goorn = Location::factory()->create([
        'name' => 'Sportcentrum Koggenhal',
        'city' => 'De Goorn',
        'description' => ['nl' => 'Alleen Nederlandse omschrijving.'],
    ]);

    $this->get(route('locations.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/locations-index')
            ->has('locations', 2)
            ->where('locations.0.id', $goorn->id)
            ->where('locations.0.excerpt', 'Alleen Nederlandse omschrijving.')
            ->where('locations.1.id', $alkmaar->id)
            ->where('locations.1.excerpt', 'An indoor venue for FPV drone racing.')
            ->has('seo.title'),
        );
});

test('the location index provides a useful empty state', function () {
    $this->get(route('locations.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('locations', [])
            ->has('seo.title'),
        );
});

test('a location detail exposes structured address, facilities and localized description', function () {
    $coverImage = MediaAsset::factory()
        ->named('sportpaleis-alkmaar.jpg')
        ->create([
            'alt_text' => ['en' => 'Indoor FPV race track at Sportpaleis Alkmaar'],
        ]);
    $location = Location::factory()->create([
        'name' => 'Sportpaleis Alkmaar',
        'slug' => 'sportpaleis-alkmaar',
        'description' => [
            'en' => 'An indoor venue for FPV drone racing.',
            'nl' => 'Een binnenlocatie voor FPV-droneraces.',
        ],
        'street' => 'Terborchlaan',
        'house_number' => '200',
        'postal_code' => '1816 LE',
        'city' => 'Alkmaar',
        'country_code' => 'NL',
        'environment' => LocationEnvironment::Indoor,
        'floor_size_square_metres' => 1200,
        'ceiling_height_metres' => '8.50',
        'facilities' => ['parking', 'power'],
        'website_url' => 'https://example.com/venue',
        'cover_image_id' => $coverImage->id,
    ]);

    $response = $this->get(route('locations.show', ['location' => $location->slug]));

    $coverImage->refresh();

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/location-show')
            ->where('location.id', $location->id)
            ->where('location.name', 'Sportpaleis Alkmaar')
            ->where('location.slug', 'sportpaleis-alkmaar')
            ->where('location.description', 'An indoor venue for FPV drone racing.')
            ->where('location.street', 'Terborchlaan')
            ->where('location.houseNumber', '200')
            ->where('location.postalCode', '1816 LE')
            ->where('location.city', 'Alkmaar')
            ->where('location.environment', LocationEnvironment::Indoor->value)
            ->where('location.floorSizeSquareMetres', 1200)
            ->where('location.ceilingHeightMetres', '8.50')
            ->where('location.facilities', ['parking', 'power'])
            ->where('location.websiteUrl', 'https://example.com/venue')
            ->where('location.mapEmbedUrl', 'https://maps.google.com/maps?q=Sportpaleis%20Alkmaar%2C%20Terborchlaan%20200%2C%201816%20LE%20Alkmaar%2C%20NL&z=15&output=embed')
            ->where('location.mapUrl', 'https://www.google.com/maps/search/?api=1&query=Sportpaleis%20Alkmaar%2C%20Terborchlaan%20200%2C%201816%20LE%20Alkmaar%2C%20NL')
            ->where('location.image.src', $coverImage->url())
            ->where('location.image.alt', 'Indoor FPV race track at Sportpaleis Alkmaar')
            ->where('seo.title', 'Sportpaleis Alkmaar')
            ->where('seo.canonicalUrl', rtrim((string) config('app.url'), '/').'/locations/sportpaleis-alkmaar'),
        );
});

test('a location falls back to another non-empty translation when the current locale is missing', function () {
    app()->setLocale('nl');
    $location = Location::factory()->create([
        'description' => ['en' => 'Only English is available.'],
    ]);

    $this->get(route('locations.show', ['location' => $location->slug]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('location.description', 'Only English is available.'),
        );

    app()->setLocale('en');
});

test('an unknown location slug is not found', function () {
    $this->get('/locations/unknown-location')->assertNotFound();
});

test('a location detail shows only upcoming publicly visible events at that location', function () {
    $location = Location::factory()->create();
    $otherLocation = Location::factory()->create();

    $upcomingEvent = Event::factory()->for($location)->published()->create([
        'title' => 'Vrijdagtraining',
        'starts_at' => now()->addWeek(),
    ]);
    Event::factory()->for($location)->create([
        'title' => 'Conceptevent',
        'starts_at' => now()->addWeek(),
    ]);
    Event::factory()->for($location)->published()->create([
        'title' => 'Verleden event',
        'starts_at' => now()->subWeek(),
    ]);
    Event::factory()->for($otherLocation)->published()->create([
        'title' => 'Ander locatie event',
        'starts_at' => now()->addWeek(),
    ]);

    $this->get(route('locations.show', ['location' => $location->slug]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('upcomingEvents', 1)
            ->where('upcomingEvents.0.id', $upcomingEvent->id)
            ->where('upcomingEvents.0.status', EventStatus::Published->value),
        );
});

test('a location without upcoming events keeps a useful empty result contract', function () {
    $location = Location::factory()->create();

    $this->get(route('locations.show', ['location' => $location->slug]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('upcomingEvents', []),
        );
});
