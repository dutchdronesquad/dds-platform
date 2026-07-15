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

test('events can be created through their factory', function () {
    $event = Event::factory()
        ->training()
        ->published()
        ->withCoverImage()
        ->inSeason()
        ->create()
        ->load(['location', 'season', 'coverImage']);

    $this->assertModelExists($event);

    expect($event)
        ->title->toBeString()
        ->content->toBeString()
        ->starts_at->toBeInstanceOf(CarbonImmutable::class)
        ->ends_at->toBeInstanceOf(CarbonImmutable::class)
        ->published_at->toBeInstanceOf(CarbonImmutable::class)
        ->registration_opens_at->toBeInstanceOf(CarbonImmutable::class)
        ->registration_deadline_at->toBeInstanceOf(CarbonImmutable::class)
        ->status->toBe(EventStatus::Published)
        ->type->toBe(EventType::Training)
        ->registration_status->toBe(EventRegistrationStatus::Closed)
        ->location->toBeInstanceOf(Location::class)
        ->season->toBeInstanceOf(Season::class)
        ->coverImage->toBeInstanceOf(MediaAsset::class)
        ->price_cents->toBeInt()
        ->capacity->toBeInt();
});

test('event enum values cover the supported domain states', function () {
    expect(array_column(EventType::cases(), 'value'))->toBe([
        'training',
        'race',
        'demo',
        'workshop',
        'other',
    ])->and(array_column(EventStatus::cases(), 'value'))->toBe([
        'draft',
        'published',
        'cancelled',
    ])->and(array_column(EventRegistrationStatus::cases(), 'value'))->toBe([
        'closed',
        'open',
        'waitlist',
        'full',
    ]);
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

test('new events mirror their database defaults before persistence', function () {
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

test('cancelled events remain stored as a visible lifecycle state', function () {
    $event = Event::factory()->cancelled()->create();

    expect($event->status)->toBe(EventStatus::Cancelled);
});
