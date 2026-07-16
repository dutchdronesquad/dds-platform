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
            'registration_opens_at' => '2026-09-15 10:00:00',
            'registration_deadline_at' => '2026-10-14 23:59:00',
            'registration_status' => EventRegistrationStatus::Open->value,
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
        ->registration_status->toBe(EventRegistrationStatus::Open)
        ->location->id->toBe($location->id)
        ->season->id->toBe($season->id)
        ->coverImage->id->toBe($coverImage->id)
        ->price_cents->toBe(1500)
        ->capacity->toBe(16);
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
    'registration status' => 'registration_status',
]);

test('new events default to a closed draft', function () {
    $event = new Event;

    expect($event->status)->toBe(EventStatus::Draft)
        ->and($event->type)->toBe(EventType::Other)
        ->and($event->registration_status)->toBe(EventRegistrationStatus::Closed);
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
