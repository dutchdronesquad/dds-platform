<?php

use App\Enums\EventRegistrationStatus;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Location;
use App\Models\MediaAsset;
use App\Models\Season;
use Carbon\CarbonImmutable;
use Database\Seeders\DevelopmentEventSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function () {
    CarbonImmutable::setTestNow('2026-07-15 10:00:00');
    Storage::fake('public');
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

test('the local demo event command creates the same representative dataset on repeated runs', function () {
    $this->artisan('dds:seed-demo-events')
        ->expectsOutput('7 demo-events zijn aangemaakt of bijgewerkt.')
        ->assertSuccessful();

    $firstRun = demoEventsSnapshot();
    $firstIds = demoRecordIds();

    $this->artisan('dds:seed-demo-events')->assertSuccessful();

    $events = Event::query()
        ->whereIn('slug', DevelopmentEventSeeder::EVENT_SLUGS)
        ->get()
        ->keyBy('slug');

    expect(demoEventsSnapshot())
        ->toBe($firstRun)
        ->and(demoRecordIds())->toBe($firstIds)
        ->and($firstRun)->toHaveCount(7)
        ->and($events[DevelopmentEventSeeder::EVENT_SLUGS[0]]->registration_status)->toBe(EventRegistrationStatus::Open)
        ->and($events[DevelopmentEventSeeder::EVENT_SLUGS[1]]->registration_status)->toBe(EventRegistrationStatus::Open)
        ->and($events[DevelopmentEventSeeder::EVENT_SLUGS[2]]->registration_status)->toBe(EventRegistrationStatus::Waitlist)
        ->and($events[DevelopmentEventSeeder::EVENT_SLUGS[3]]->registration_status)->toBe(EventRegistrationStatus::Full)
        ->and($events[DevelopmentEventSeeder::EVENT_SLUGS[4]]->registration_status)->toBe(EventRegistrationStatus::Closed)
        ->and($events[DevelopmentEventSeeder::EVENT_SLUGS[5]]->status)->toBe(EventStatus::Cancelled)
        ->and($events[DevelopmentEventSeeder::EVENT_SLUGS[0]]->title)->toBe('FPV vliegavond - juli 2026')
        ->and($events[DevelopmentEventSeeder::EVENT_SLUGS[1]]->title)->toBe('Indoor FPV-clubrace met kwalificaties en finales')
        ->and($events[DevelopmentEventSeeder::EVENT_SLUGS[2]]->title)->toBe('Blacklight FPV-oefenavond')
        ->and($events[DevelopmentEventSeeder::EVENT_SLUGS[3]]->title)->toBe('FPV-kennismakingsavond')
        ->and($events[DevelopmentEventSeeder::EVENT_SLUGS[4]]->title)->toBe('Indoor FPV-clubrace')
        ->and($events[DevelopmentEventSeeder::EVENT_SLUGS[5]]->title)->toBe('FPV uitwijkavond - augustus 2026')
        ->and($events[DevelopmentEventSeeder::EVENT_SLUGS[6]]->title)->toBe('Workshop FPV-racevoorbereiding')
        ->and($events->pluck('title')->unique()->count())->toBe($events->count())
        ->and(Event::query()->whereNull('content')->whereIn('slug', DevelopmentEventSeeder::EVENT_SLUGS)->count())
        ->toBe(1)
        ->and(Event::query()->whereNull('cover_image_id')->whereIn('slug', DevelopmentEventSeeder::EVENT_SLUGS)->count())
        ->toBeGreaterThan(0)
        ->and(Event::query()->where('price_cents', 0)->whereIn('slug', DevelopmentEventSeeder::EVENT_SLUGS)->count())
        ->toBe(1)
        ->and(Event::query()->whereNull('price_cents')->whereIn('slug', DevelopmentEventSeeder::EVENT_SLUGS)->count())
        ->toBe(1)
        ->and(Location::query()->whereIn('slug', DevelopmentEventSeeder::LOCATION_SLUGS)->count())
        ->toBe(3)
        ->and(MediaAsset::query()->whereIn('path', DevelopmentEventSeeder::MEDIA_PATHS)->count())
        ->toBe(3)
        ->and(Season::query()->where('name', DevelopmentEventSeeder::SEASON_NAME)->count())
        ->toBe(1);

    $firstTraining = Event::query()->where('slug', DevelopmentEventSeeder::EVENT_SLUGS[0])->firstOrFail();

    expect($firstTraining->starts_at->setTimezone('Europe/Amsterdam')->format('H:i'))
        ->toBe('18:00')
        ->and($firstTraining->registration_deadline_at?->setTimezone('Europe/Amsterdam')->format('Y-m-d H:i'))
        ->toBe('2026-07-18 23:59');

    $yearAheadWorkshop = Event::query()->where('slug', DevelopmentEventSeeder::EVENT_SLUGS[6])->firstOrFail();

    expect($yearAheadWorkshop->starts_at->setTimezone('Europe/Amsterdam')->format('Y-m-d H:i'))
        ->toBe('2027-07-15 13:00');

    $closedRace = Event::query()->where('slug', DevelopmentEventSeeder::EVENT_SLUGS[4])->firstOrFail();
    $fullTraining = Event::query()->where('slug', DevelopmentEventSeeder::EVENT_SLUGS[3])->firstOrFail();

    expect($closedRace->registration_opens_at?->isBefore($closedRace->registration_deadline_at))
        ->toBeTrue()
        ->and($closedRace->registration_deadline_at?->isBefore($closedRace->starts_at))
        ->toBeTrue()
        ->and($fullTraining->registration_opens_at?->isBefore($fullTraining->registration_deadline_at))
        ->toBeTrue()
        ->and($fullTraining->registration_deadline_at?->isBefore($fullTraining->starts_at))
        ->toBeTrue();

    Storage::disk('public')->assertExists(DevelopmentEventSeeder::MEDIA_PATH_PREFIX.'pilot-at-training.jpg');
    Storage::disk('public')->assertExists(DevelopmentEventSeeder::MEDIA_PATH_PREFIX.'race-control.jpg');
    Storage::disk('public')->assertExists(DevelopmentEventSeeder::MEDIA_PATH_PREFIX.'indoor-track.jpg');
});

test('demo event dates follow the current date across calendar years', function () {
    CarbonImmutable::setTestNow(
        CarbonImmutable::parse('2027-12-20 10:00:00', 'Europe/Amsterdam'),
    );

    $this->artisan('dds:seed-demo-events')->assertSuccessful();

    $firstEvent = Event::query()
        ->where('slug', DevelopmentEventSeeder::EVENT_SLUGS[0])
        ->firstOrFail();
    $lastEvent = Event::query()
        ->where('slug', DevelopmentEventSeeder::EVENT_SLUGS[5])
        ->firstOrFail();
    $yearAheadEvent = Event::query()
        ->where('slug', DevelopmentEventSeeder::EVENT_SLUGS[6])
        ->firstOrFail();

    expect($firstEvent->starts_at->setTimezone('Europe/Amsterdam')->format('Y-m-d H:i'))
        ->toBe('2027-12-26 18:00')
        ->and($lastEvent->starts_at->setTimezone('Europe/Amsterdam')->format('Y-m-d H:i'))
        ->toBe('2028-01-30 18:00')
        ->and($yearAheadEvent->starts_at->setTimezone('Europe/Amsterdam')->format('Y-m-d H:i'))
        ->toBe('2028-12-19 13:00');
});

test('the dataset mirrors the published DDS training and location information', function () {
    $this->artisan('dds:seed-demo-events')->assertSuccessful();

    $season = Season::query()->where('name', DevelopmentEventSeeder::SEASON_NAME)->firstOrFail();
    $sportpaleis = Location::query()->where('slug', DevelopmentEventSeeder::LOCATION_SLUGS[0])->firstOrFail();
    $koggenhal = Location::query()->where('slug', DevelopmentEventSeeder::LOCATION_SLUGS[1])->firstOrFail();
    $oosterhout = Location::query()->where('slug', DevelopmentEventSeeder::LOCATION_SLUGS[2])->firstOrFail();
    $training = Event::query()->where('slug', DevelopmentEventSeeder::EVENT_SLUGS[0])->firstOrFail();

    expect($season->price_cents)
        ->toBe(9000)
        ->and($season->ticket_capacity)->toBeNull()
        ->and($sportpaleis->street)->toBe('Terborchlaan')
        ->and($sportpaleis->house_number)->toBe('200')
        ->and($sportpaleis->floor_size_square_metres)->toBe(2000)
        ->and($sportpaleis->ceiling_height_metres)->toBe('11.00')
        ->and($koggenhal->city)->toBe('De Goorn')
        ->and($koggenhal->floor_size_square_metres)->toBe(1350)
        ->and($oosterhout->name)->toBe('Sporthal Oosterhout')
        ->and($oosterhout->street)->toBe('Vondelstraat')
        ->and($oosterhout->house_number)->toBe('35')
        ->and($oosterhout->postal_code)->toBe('1813 AA')
        ->and($oosterhout->city)->toBe('Alkmaar')
        ->and($oosterhout->latitude)->toBe('52.6213000')
        ->and($oosterhout->longitude)->toBe('4.7514000')
        ->and($training->price_cents)->toBe(1500)
        ->and($training->capacity)->toBe(16)
        ->and($training->starts_at->setTimezone('Europe/Amsterdam')->format('l H:i'))->toBe('Sunday 18:00')
        ->and($training->ends_at?->setTimezone('Europe/Amsterdam')->format('H:i'))->toBe('21:00')
        ->and($training->registration_deadline_at?->setTimezone('Europe/Amsterdam')->format('l H:i'))->toBe('Saturday 23:59');
});

test('each demo event opens registration two calendar weeks before it starts at the same local time', function () {
    CarbonImmutable::setTestNow(
        CarbonImmutable::parse('2026-03-15 10:00:00', 'Europe/Amsterdam'),
    );

    $this->artisan('dds:seed-demo-events')->assertSuccessful();

    Event::query()
        ->whereIn('slug', DevelopmentEventSeeder::EVENT_SLUGS)
        ->get()
        ->each(function (Event $event): void {
            expect($event->registration_opens_at?->toIso8601String())
                ->toBe(
                    $event->starts_at
                        ->setTimezone('Europe/Amsterdam')
                        ->subWeeks(2)
                        ->utc()
                        ->toIso8601String(),
                );
        });
});

test('reset removes only unreferenced demo records and preserves real content', function () {
    $this->artisan('dds:seed-demo-events')->assertSuccessful();

    $demoLocation = Location::query()
        ->where('slug', DevelopmentEventSeeder::LOCATION_SLUG_PREFIX.'sportpaleis-alkmaar')
        ->firstOrFail();
    $demoSeason = Season::query()->where('name', DevelopmentEventSeeder::SEASON_NAME)->firstOrFail();
    $demoCover = MediaAsset::query()
        ->where('path', DevelopmentEventSeeder::MEDIA_PATH_PREFIX.'pilot-at-training.jpg')
        ->firstOrFail();
    $realEvent = Event::factory()->published()->create([
        'location_id' => $demoLocation->id,
        'season_id' => $demoSeason->id,
        'cover_image_id' => $demoCover->id,
        'slug' => 'real-event-that-uses-demo-supporting-records',
    ]);
    $prefixedLocation = Location::factory()->create([
        'slug' => DevelopmentEventSeeder::LOCATION_SLUG_PREFIX.'community-record',
    ]);
    $prefixedCover = MediaAsset::factory()->create([
        'disk' => 'public',
        'path' => DevelopmentEventSeeder::MEDIA_PATH_PREFIX.'community-record.jpg',
    ]);
    $prefixedEvent = Event::factory()->published()->create([
        'location_id' => $prefixedLocation->id,
        'cover_image_id' => $prefixedCover->id,
        'slug' => DevelopmentEventSeeder::EVENT_SLUG_PREFIX.'community-record',
    ]);

    $this->artisan('dds:seed-demo-events --reset')
        ->expectsOutput('7 demo-events verwijderd; overige content is behouden.')
        ->assertSuccessful();

    expect(Event::query()->whereIn('slug', DevelopmentEventSeeder::EVENT_SLUGS)->count())
        ->toBe(0)
        ->and($realEvent->fresh())->not->toBeNull()
        ->and($demoLocation->fresh())->not->toBeNull()
        ->and($demoSeason->fresh())->not->toBeNull()
        ->and($demoCover->fresh())->not->toBeNull()
        ->and($prefixedEvent->fresh())->not->toBeNull()
        ->and($prefixedLocation->fresh())->not->toBeNull()
        ->and($prefixedCover->fresh())->not->toBeNull();

    Storage::disk('public')->assertExists(DevelopmentEventSeeder::MEDIA_PATH_PREFIX.'pilot-at-training.jpg');
    Storage::disk('public')->assertMissing(DevelopmentEventSeeder::MEDIA_PATH_PREFIX.'race-control.jpg');
    Storage::disk('public')->assertMissing(DevelopmentEventSeeder::MEDIA_PATH_PREFIX.'indoor-track.jpg');
});

test('reset works before the optional articles migration is applied', function () {
    $this->artisan('dds:seed-demo-events')->assertSuccessful();

    $connection = DB::connection();
    $connection->flushQueryLog();
    $connection->enableQueryLog();

    Schema::shouldReceive('hasTable')
        ->once()
        ->with('articles')
        ->andReturnFalse();

    try {
        $this->artisan('dds:seed-demo-events --reset')
            ->expectsOutput('7 demo-events verwijderd; overige content is behouden.')
            ->assertSuccessful();

        $queries = collect($connection->getQueryLog())->pluck('query');
    } finally {
        $connection->disableQueryLog();
        $connection->flushQueryLog();
    }

    expect(Event::query()->whereIn('slug', DevelopmentEventSeeder::EVENT_SLUGS)->count())
        ->toBe(0)
        ->and($queries->contains(
            static fn (string $query): bool => Str::contains($query, 'articles'),
        ))
        ->toBeFalse();
});

test('the demo event command refuses to run in production', function (string $command) {
    $originalEnvironment = app()->environment();
    app()->detectEnvironment(fn (): string => 'production');

    try {
        $this->artisan($command)
            ->expectsOutput('De DDS demo-events mogen alleen lokaal worden beheerd.')
            ->assertFailed();
    } finally {
        app()->detectEnvironment(fn (): string => $originalEnvironment);
    }

    expect(Event::query()->where('slug', 'like', DevelopmentEventSeeder::EVENT_SLUG_PREFIX.'%')->count())
        ->toBe(0);
})->with([
    'create' => 'dds:seed-demo-events',
    'reset' => 'dds:seed-demo-events --reset',
]);

test('direct demo seeder mutations refuse to run in production', function (string $method) {
    $seeder = app(DevelopmentEventSeeder::class);
    $originalEnvironment = app()->environment();
    app()->detectEnvironment(fn (): string => 'production');

    try {
        expect(fn () => match ($method) {
            'run' => $seeder->run(),
            'reset' => $seeder->reset(),
        })
            ->toThrow(RuntimeException::class, 'De DDS demo-events mogen alleen lokaal worden beheerd.');
    } finally {
        app()->detectEnvironment(fn (): string => $originalEnvironment);
    }
})->with(['run', 'reset']);

/**
 * @return list<array<string, int|string|null>>
 */
function demoEventsSnapshot(): array
{
    return Event::query()
        ->whereIn('slug', DevelopmentEventSeeder::EVENT_SLUGS)
        ->with(['coverImage:id,path', 'location:id,slug', 'season:id,name'])
        ->oldest('slug')
        ->get()
        ->map(fn (Event $event): array => [
            'slug' => $event->slug,
            'title' => $event->title,
            'content' => $event->content,
            'starts_at' => $event->starts_at->toIso8601String(),
            'ends_at' => $event->ends_at?->toIso8601String(),
            'status' => $event->status->value,
            'type' => $event->type->value,
            'price_cents' => $event->price_cents,
            'capacity' => $event->capacity,
            'registration_opens_at' => $event->registration_opens_at?->toIso8601String(),
            'registration_deadline_at' => $event->registration_deadline_at?->toIso8601String(),
            'registration_status' => $event->registration_status->value,
            'registration_url' => $event->registration_url,
            'location' => $event->location->slug,
            'season' => $event->season?->name,
            'cover' => $event->coverImage?->path,
        ])
        ->all();
}

/** @return array<string, list<int>> */
function demoRecordIds(): array
{
    return [
        'events' => Event::query()->whereIn('slug', DevelopmentEventSeeder::EVENT_SLUGS)->oldest('id')->pluck('id')->all(),
        'locations' => Location::query()->whereIn('slug', DevelopmentEventSeeder::LOCATION_SLUGS)->oldest('id')->pluck('id')->all(),
        'media' => MediaAsset::query()->whereIn('path', DevelopmentEventSeeder::MEDIA_PATHS)->oldest('id')->pluck('id')->all(),
        'seasons' => Season::query()->where('name', DevelopmentEventSeeder::SEASON_NAME)->oldest('id')->pluck('id')->all(),
    ];
}
