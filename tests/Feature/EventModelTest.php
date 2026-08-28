<?php

use App\Enums\EventRegistrationStatus;
use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Models\Event;
use App\Models\Location;
use App\Models\MediaAsset;
use App\Models\Season;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

test('events expose their domain casts and relationships', function () {
    $location = Location::factory()->create();
    $season = Season::factory()->create();
    $coverImage = MediaAsset::factory()->create();
    $event = Event::query()
        ->create([
            'location_id' => $location->id,
            'season_id' => $season->id,
            'cover_image_id' => $coverImage->id,
            'title' => 'Indoor training',
            'slug' => 'indoor-training',
            'content' => 'Bring a racequad and goggles.',
            'starts_at' => '2026-10-15 17:00:00',
            'ends_at' => '2026-10-15 20:30:00',
            'published_at' => '2026-07-01 10:00:00',
            'status' => EventStatus::Published->value,
            'type' => EventType::Training->value,
            'price_cents' => '1500',
            'capacity' => '16',
            'registration_enabled' => true,
            'registration_closed_manually' => false,
            'registration_full' => false,
            'registration_waitlist_enabled' => false,
            'registration_opens_at' => '2026-09-15 10:00:00',
            'registration_deadline_at' => '2026-10-14 23:59:00',
            'registration_url' => 'https://example.com/register',
        ])
        ->refresh()
        ->load(['location', 'season', 'coverImage']);

    $this->assertModelExists($event);

    expect($event)
        ->title->toBe('Indoor training')
        ->content->toBe('Bring a racequad and goggles.')
        ->starts_at->toBeInstanceOf(CarbonImmutable::class)
        ->ends_at->toBeInstanceOf(CarbonImmutable::class)
        ->published_at->toBeInstanceOf(CarbonImmutable::class)
        ->registration_opens_at->toBeInstanceOf(CarbonImmutable::class)
        ->registration_deadline_at->toBeInstanceOf(CarbonImmutable::class)
        ->status->toBe(EventStatus::Published)
        ->type->toBe(EventType::Training)
        ->registration_enabled->toBeTrue()
        ->registration_closed_manually->toBeFalse()
        ->registration_full->toBeFalse()
        ->registration_waitlist_enabled->toBeFalse()
        ->price_cents->toBe(1500)
        ->capacity->toBe(16)
        ->and($event->location->id)->toBe($location->id)
        ->and($event->season?->id)->toBe($season->id)
        ->and($event->coverImage?->id)->toBe($coverImage->id);

    expect($event->currentRegistrationStatus(CarbonImmutable::parse('2026-10-01')))
        ->toBe(EventRegistrationStatus::Open);
});

test('event enum values are enforced by the database', function (string $column) {
    $event = Event::factory()->create();

    expect(fn () => DB::table($event->getTable())
        ->where('id', $event->id)
        ->update([$column => 'unsupported']))
        ->toThrow(QueryException::class);
})->with([
    'status' => 'status',
    'type' => 'type',
]);

test('new events default to a closed draft', function () {
    $event = new Event;

    expect($event->status)->toBe(EventStatus::Draft)
        ->and($event->type)->toBe(EventType::Other)
        ->and($event->registration_enabled)->toBeFalse()
        ->and($event->registration_closed_manually)->toBeFalse()
        ->and($event->registration_full)->toBeFalse()
        ->and($event->registration_waitlist_enabled)->toBeFalse();

    expect($event->currentRegistrationStatus())->toBe(EventRegistrationStatus::Closed);
});

test('registration status follows planning and manual overrides', function () {
    $event = Event::factory()->make([
        'registration_enabled' => true,
        'registration_opens_at' => '2026-09-15 10:00:00',
        'registration_deadline_at' => '2026-09-15 12:00:00',
        'registration_url' => 'https://example.com/register',
    ]);

    expect($event->currentRegistrationStatus(CarbonImmutable::parse('2026-09-15 09:59:59')))
        ->toBe(EventRegistrationStatus::Closed)
        ->and($event->registrationIsUpcoming(CarbonImmutable::parse('2026-09-15 09:59:59')))->toBeTrue()
        ->and($event->currentRegistrationStatus(CarbonImmutable::parse('2026-09-15 10:00:00')))->toBe(EventRegistrationStatus::Open)
        ->and($event->currentRegistrationStatus(CarbonImmutable::parse('2026-09-15 12:00:00')))->toBe(EventRegistrationStatus::Closed);

    $event->registration_full = true;

    expect($event->currentRegistrationStatus(CarbonImmutable::parse('2026-09-15 11:00:00')))
        ->toBe(EventRegistrationStatus::Full);

    $event->registration_waitlist_enabled = true;

    expect($event->currentRegistrationStatus(CarbonImmutable::parse('2026-09-15 11:00:00')))
        ->toBe(EventRegistrationStatus::Waitlist);

    $event->registration_closed_manually = true;

    expect($event->currentRegistrationStatus(CarbonImmutable::parse('2026-09-15 11:00:00')))
        ->toBe(EventRegistrationStatus::Closed);
});

test('deleting a cover image preserves the event and clears the reference', function () {
    $event = Event::factory()->withCoverImage()->create()->load('coverImage');
    $coverImage = $event->coverImage;

    $coverImage->delete();

    expect($event->refresh()->cover_image_id)->toBeNull();
});

test('only published or cancelled events whose publication date has passed are publicly visible', function () {
    $publishedEvent = Event::factory()->published()->create([
        'published_at' => now()->subMinute(),
    ]);
    $cancelledEvent = Event::factory()->cancelled()->create([
        'published_at' => now()->subDay(),
    ]);
    Event::factory()->create(['published_at' => now()->subDay()]);
    Event::factory()->published()->create(['published_at' => now()->addDay()]);
    Event::factory()->cancelled()->create(['published_at' => null]);

    $publicEvents = Event::query()->publiclyVisible()->get();

    expect($publicEvents)->toHaveCount(2)
        ->and($publicEvents->contains($publishedEvent))->toBeTrue()
        ->and($publicEvents->contains($cancelledEvent))->toBeTrue()
        ->and($publishedEvent->isPubliclyVisible())->toBeTrue()
        ->and($cancelledEvent->isPubliclyVisible())->toBeTrue();
});
