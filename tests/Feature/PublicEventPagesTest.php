<?php

use App\Enums\EventRegistrationStatus;
use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Models\Event;
use App\Models\Location;
use App\Models\MediaAsset;
use App\Models\Season;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

test('the event index lists only published upcoming events in chronological order', function () {
    $laterEvent = Event::factory()->published()->create([
        'title' => 'Late summer race',
        'starts_at' => now()->addWeeks(2),
    ]);
    $nextEvent = Event::factory()->published()->training()->create([
        'title' => 'Next training',
        'starts_at' => now()->addWeek(),
        'season_id' => Season::factory()->create(['name' => 'Seizoen 2026/27']),
        'price_cents' => 1500,
        'capacity' => 16,
        'registration_status' => EventRegistrationStatus::Open,
    ]);
    $cancelledEvent = Event::factory()->cancelled()->create([
        'title' => 'Cancelled workshop',
        'starts_at' => now()->addDays(10),
        'published_at' => now()->subDay(),
        'type' => EventType::Workshop,
    ]);

    Event::factory()->create([
        'title' => 'Draft event',
        'starts_at' => now()->addDay(),
    ]);
    Event::factory()->published()->create([
        'title' => 'Scheduled publication',
        'starts_at' => now()->addDay(),
        'published_at' => now()->addHour(),
    ]);
    Event::factory()->published()->create([
        'title' => 'Past event',
        'starts_at' => now()->subDay(),
    ]);

    $this->get(route('events.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/events-index')
            ->where('activeType', null)
            ->where('events.total', 3)
            ->has('events.data', 3)
            ->where('events.data.0.id', $nextEvent->id)
            ->where('events.data.0.priceCents', 1500)
            ->where('events.data.0.capacity', 16)
            ->where('events.data.0.registrationStatus', EventRegistrationStatus::Open->value)
            ->where('events.data.0.season.name', 'Seizoen 2026/27')
            ->where('events.data.0.location.name', $nextEvent->location->name)
            ->where('events.data.1.id', $cancelledEvent->id)
            ->where('events.data.1.status', EventStatus::Cancelled->value)
            ->where('events.data.2.id', $laterEvent->id)
            ->where('events.data.2.season', null)
            ->has('typeFilters', 5)
            ->has('seo.title'),
        );
});

test('the event index filters upcoming events by type', function () {
    $training = Event::factory()->published()->training()->create([
        'starts_at' => now()->addWeek(),
    ]);
    Event::factory()->published()->create([
        'starts_at' => now()->addWeek(),
        'type' => EventType::Race,
    ]);

    $this->get(route('events.index', ['type' => EventType::Training->value]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/events-index')
            ->where('activeType', EventType::Training->value)
            ->where('events.total', 1)
            ->has('events.data', 1)
            ->where('events.data.0.id', $training->id)
            ->where('events.data.0.type', EventType::Training->value),
        );
});

test('events with the same start time keep a deterministic creation order', function () {
    $startsAt = now()->addWeek();
    $firstEvent = Event::factory()->published()->create(['starts_at' => $startsAt]);
    $secondEvent = Event::factory()->published()->create(['starts_at' => $startsAt]);
    $thirdEvent = Event::factory()->published()->create(['starts_at' => $startsAt]);

    $this->get(route('events.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('events.data.0.id', $firstEvent->id)
            ->where('events.data.1.id', $secondEvent->id)
            ->where('events.data.2.id', $thirdEvent->id),
        );
});

test('unknown event types safely fall back to the complete event index', function () {
    Event::factory()->published()->create([
        'starts_at' => now()->addWeek(),
    ]);

    $this->get(route('events.index', ['type' => 'unsupported']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('activeType', null)
            ->where('events.total', 1),
        );
});

test('the event index provides a useful empty result contract', function () {
    Event::factory()->published()->create([
        'starts_at' => now()->addWeek(),
        'type' => EventType::Race,
    ]);

    $this->get(route('events.index', ['type' => EventType::Training->value]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('activeType', EventType::Training->value)
            ->where('events.total', 0)
            ->where('events.current_page', 1)
            ->where('events.last_page', 1)
            ->has('events.data', 0)
            ->where('typeFilters', [
                ['value' => EventType::Training->value, 'label' => 'Trainingen'],
                ['value' => EventType::Race->value, 'label' => 'Races'],
                ['value' => EventType::Demo->value, 'label' => 'Demo’s'],
                ['value' => EventType::Workshop->value, 'label' => 'Workshops'],
                ['value' => EventType::Other->value, 'label' => 'Overig'],
            ])
            ->has('seo.title')
            ->has('seo.description'),
        );
});

test('the homepage uses the next three published events instead of placeholder data', function () {
    Event::factory()->published()->create([
        'title' => 'Fourth event',
        'starts_at' => now()->addWeeks(4),
    ]);
    $thirdEvent = Event::factory()->published()->create([
        'title' => 'Third event',
        'starts_at' => now()->addWeeks(3),
    ]);
    $secondEvent = Event::factory()->published()->create([
        'title' => 'Second event',
        'starts_at' => now()->addWeeks(2),
    ]);
    $firstEvent = Event::factory()->published()->training()->create([
        'title' => 'First event',
        'starts_at' => now()->addWeek(),
        'season_id' => Season::factory()->create(['name' => 'Seizoen 2026/27']),
    ]);
    Event::factory()->create([
        'title' => 'Draft event',
        'starts_at' => now()->addDay(),
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('welcome')
            ->has('upcomingEvents', 3)
            ->where('upcomingEvents.0.id', $firstEvent->id)
            ->where('upcomingEvents.0.type', EventType::Training->value)
            ->where('upcomingEvents.0.season.name', 'Seizoen 2026/27')
            ->where('upcomingEvents.1.id', $secondEvent->id)
            ->where('upcomingEvents.2.id', $thirdEvent->id)
            ->missing('upcomingEventsArePlaceholder'),
        );
});

test('a published training detail exposes practical and registration information', function () {
    $location = Location::factory()->create([
        'name' => 'Sportpaleis Alkmaar',
        'city' => 'Alkmaar',
        'street' => 'Terborchlaan',
        'house_number' => '200',
        'postal_code' => '1816 LE',
    ]);
    $event = Event::factory()
        ->for($location)
        ->for(Season::factory()->state(['name' => 'Wintercompetitie 2026/27']))
        ->published()
        ->training()
        ->create([
            'title' => 'Indoor training round 01',
            'slug' => 'indoor-training-round-01',
            'content' => 'Neem je racequad, goggles en voldoende accu’s mee.',
            'starts_at' => '2026-10-15 17:00:00',
            'ends_at' => '2026-10-15 20:30:00',
            'price_cents' => 1500,
            'capacity' => 15,
            'cover_image_id' => MediaAsset::factory()->create([
                'disk' => 'public',
                'path' => 'events/indoor-training-round-01.jpg',
                'alt_text' => ['en' => 'Pilots preparing for an indoor training heat'],
            ]),
            'registration_opens_at' => '2026-09-15 10:00:00',
            'registration_deadline_at' => '2026-10-14 23:59:00',
            'registration_status' => EventRegistrationStatus::Open,
            'registration_url' => 'https://example.com/register',
        ]);

    $this->get(route('events.show', ['event' => $event->slug]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/event-show')
            ->where('event.id', $event->id)
            ->where('event.title', 'Indoor training round 01')
            ->where('event.slug', 'indoor-training-round-01')
            ->where('event.type', EventType::Training->value)
            ->where('event.content', 'Neem je racequad, goggles en voldoende accu’s mee.')
            ->where('event.startsAt', $event->starts_at->toIso8601String())
            ->where('event.endsAt', $event->ends_at?->toIso8601String())
            ->where('event.location.name', 'Sportpaleis Alkmaar')
            ->where('event.location.city', 'Alkmaar')
            ->where('event.location.street', 'Terborchlaan')
            ->where('event.location.houseNumber', '200')
            ->where('event.location.postalCode', '1816 LE')
            ->where('event.location.mapEmbedUrl', 'https://maps.google.com/maps?q=Sportpaleis%20Alkmaar%2C%20Terborchlaan%20200%2C%201816%20LE%20Alkmaar%2C%20NL&z=15&output=embed')
            ->where('event.location.mapUrl', 'https://www.google.com/maps/search/?api=1&query=Sportpaleis%20Alkmaar%2C%20Terborchlaan%20200%2C%201816%20LE%20Alkmaar%2C%20NL')
            ->where('event.priceCents', 1500)
            ->where('event.capacity', 15)
            ->where('event.registrationStatus', EventRegistrationStatus::Open->value)
            ->where('event.season.name', 'Wintercompetitie 2026/27')
            ->where('event.registrationOpensAt', $event->registration_opens_at?->toIso8601String())
            ->where('event.registrationDeadlineAt', $event->registration_deadline_at?->toIso8601String())
            ->where('event.registrationUrl', 'https://example.com/register')
            ->where('event.image.src', Storage::disk('public')->url('events/indoor-training-round-01.jpg'))
            ->where('event.image.alt', 'Pilots preparing for an indoor training heat')
            ->where('seo.title', 'Indoor training round 01')
            ->where('seo.canonicalUrl', rtrim((string) config('app.url'), '/').'/events/indoor-training-round-01'),
        );
});

test('a previously published cancelled event remains public with its state', function () {
    $event = Event::factory()->cancelled()->create([
        'slug' => 'cancelled-race',
        'published_at' => now()->subDay(),
        'price_cents' => 2000,
        'capacity' => 36,
        'registration_status' => EventRegistrationStatus::Open,
        'registration_url' => 'https://example.com/stale-registration-link',
    ]);

    $this->get(route('events.show', ['event' => $event->slug]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('event.status', EventStatus::Cancelled->value)
            ->where('event.priceCents', 2000)
            ->where('event.capacity', 36)
            ->where('event.registrationStatus', EventRegistrationStatus::Open->value)
            ->where('event.registrationUrl', 'https://example.com/stale-registration-link'),
        );
});

test('unpublished events are not public', function (array $attributes) {
    $event = Event::factory()->create($attributes);

    $this->get(route('events.show', ['event' => $event->slug]))->assertNotFound();
})->with([
    'draft' => [[]],
    'scheduled publication' => [[
        'status' => EventStatus::Published,
        'published_at' => now()->addDay(),
    ]],
    'cancelled before publication' => [[
        'status' => EventStatus::Cancelled,
        'published_at' => null,
    ]],
]);
