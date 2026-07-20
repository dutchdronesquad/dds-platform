<?php

use App\Enums\EventRegistrationStatus;
use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Enums\Role;
use App\Models\Event;
use App\Models\Location;
use App\Models\Season;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RolesAndPermissionsSeeder::class);
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

test('event management requires a management role and event permission', function () {
    $this->get(route('admin.events.index'))->assertRedirect(route('login'));

    $user = User::factory()->create();
    $editor = User::factory()->create();
    $editor->assignRole(Role::Editor->value);
    $event = Event::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.events.index'))
        ->assertForbidden();

    expect($user->can('view', $event))->toBeFalse()
        ->and($editor->can('view', $event))->toBeTrue();
});

test('admins can review events with operational status and actions', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $event = Event::factory()->published()->training()->inSeason()->create([
        'title' => 'Vrijdagtraining',
        'slug' => 'vrijdagtraining',
        'registration_status' => EventRegistrationStatus::Open,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.events.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/events/index')
            ->where('summary.total', 1)
            ->where('summary.drafts', 0)
            ->where('summary.published', 1)
            ->where('summary.cancelled', 0)
            ->where('canCreate', true)
            ->where('canManageSeasons', true)
            ->has('events.data', 1)
            ->where('events.data.0.id', $event->id)
            ->where('events.data.0.title', 'Vrijdagtraining')
            ->where('events.data.0.status', EventStatus::Published->value)
            ->where('events.data.0.type', EventType::Training->value)
            ->where('events.data.0.registrationStatus', EventRegistrationStatus::Open->value)
            ->where('events.data.0.capabilities.update', true)
            ->where('events.data.0.capabilities.delete', true)
            ->where('events.data.0.capabilities.publish', true)
            ->where('events.data.0.capabilities.cancel', true),
        );
});

test('admins can open event forms with complete options and editable values', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $firstLocation = Location::factory()->create([
        'name' => 'Alpha Hal',
        'city' => 'Alkmaar',
    ]);
    Location::factory()->create([
        'name' => 'Zulu Hal',
        'city' => 'Zwolle',
    ]);
    $season = Season::factory()->create(['name' => 'Wintercompetitie']);
    $event = Event::factory()->published()->create([
        'location_id' => $firstLocation->id,
        'season_id' => $season->id,
        'title' => 'Finalerace',
        'slug' => 'finalerace',
        'price_cents' => 1234,
        'registration_status' => EventRegistrationStatus::Waitlist,
        'registration_url' => 'https://example.com/wachtlijst',
    ]);
    $eventWithoutOptionalValues = Event::factory()->create([
        'location_id' => $firstLocation->id,
        'ends_at' => null,
        'price_cents' => null,
        'capacity' => null,
        'registration_opens_at' => null,
        'registration_deadline_at' => null,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.events.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/events/create')
            ->where('canManageSeasons', true)
            ->has('options.locations', 2)
            ->where('options.locations.0.label', 'Alpha Hal — Alkmaar')
            ->where('options.locations.1.label', 'Zulu Hal — Zwolle')
            ->has('options.seasons', 1)
            ->where('options.seasons.0.label', 'Wintercompetitie')
            ->has('options.types', 5)
            ->has('options.registrationStatuses', 4),
        );

    $this->get(route('admin.events.edit', $event))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/events/edit')
            ->where('canManageSeasons', true)
            ->where('event.id', $event->id)
            ->where('event.locationId', $firstLocation->id)
            ->where('event.seasonId', $season->id)
            ->where('event.priceEuros', '12.34')
            ->where('event.registrationStatus', EventRegistrationStatus::Waitlist->value)
            ->where('event.registrationUrl', 'https://example.com/wachtlijst')
            ->where('event.capabilities.delete', true)
            ->where('event.capabilities.publish', true)
            ->where('event.capabilities.cancel', true)
            ->has('options.types', 5),
        );

    $this->get(route('admin.events.edit', $eventWithoutOptionalValues))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('event.endsAt', null)
            ->where('event.priceEuros', null)
            ->where('event.capacity', null)
            ->where('event.registrationOpensAt', null)
            ->where('event.registrationDeadlineAt', null)
            ->where('event.publishedAt', null)
            ->where('event.capabilities.cancel', false),
        );
});

test('event filters search the relevant context and keep query parameters in pagination', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $season = Season::factory()->create(['name' => 'Wintercompetitie']);

    Event::factory()->count(13)->published()->create([
        'season_id' => $season->id,
        'type' => EventType::Race,
    ]);
    Event::factory()->count(13)->training()->create([
        'season_id' => $season->id,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.events.index', [
            'search' => 'WINTER',
            'status' => [
                EventStatus::Draft->value,
                EventStatus::Published->value,
            ],
            'type' => [
                EventType::Training->value,
                EventType::Race->value,
            ],
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.search', 'WINTER')
            ->where('filters.status', [
                EventStatus::Draft->value,
                EventStatus::Published->value,
            ])
            ->where('filters.type', [
                EventType::Training->value,
                EventType::Race->value,
            ])
            ->where('events.total', 26)
            ->where('events.last_page', 2)
            ->where('events.next_page_url', function (string $url): bool {
                parse_str(parse_url($url, PHP_URL_QUERY) ?: '', $query);

                return $query['search'] === 'WINTER'
                    && $query['status'] === ['draft', 'published']
                    && $query['type'] === ['training', 'race'];
            }),
        );
});

test('situation filters show only matching upcoming events', function () {
    $referenceTime = CarbonImmutable::parse('2026-07-20 12:00:00');
    CarbonImmutable::setTestNow($referenceTime);

    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $season = Season::factory()->create();

    $closedRegistration = Event::factory()->published()->withCoverImage()->create([
        'season_id' => $season->id,
        'title' => 'Komende gesloten race',
        'starts_at' => now()->addDays(2),
        'registration_status' => EventRegistrationStatus::Closed,
    ]);
    Event::factory()->published()->withCoverImage()->create([
        'season_id' => $season->id,
        'title' => 'Komende open race',
        'starts_at' => now()->addDays(3),
        'registration_status' => EventRegistrationStatus::Open,
    ]);
    Event::factory()->withCoverImage()->create([
        'season_id' => $season->id,
        'title' => 'Gesloten conceptrace',
        'starts_at' => now()->addDays(4),
        'registration_status' => EventRegistrationStatus::Closed,
    ]);
    Event::factory()->published()->withCoverImage()->create([
        'season_id' => $season->id,
        'title' => 'Gesloten race in het verleden',
        'starts_at' => now()->subDay(),
        'registration_status' => EventRegistrationStatus::Closed,
    ]);
    $withoutSeason = Event::factory()->published()->withCoverImage()->create([
        'season_id' => null,
        'title' => 'Komende race zonder seizoen',
        'starts_at' => now()->addDays(5),
        'registration_deadline_at' => now()->addDays(4),
        'registration_status' => EventRegistrationStatus::Open,
    ]);
    Event::factory()->cancelled()->create([
        'season_id' => null,
        'title' => 'Geannuleerde race zonder seizoen',
        'starts_at' => now()->addDays(6),
    ]);
    $withoutContent = Event::factory()->published()->withCoverImage()->create([
        'season_id' => $season->id,
        'title' => 'Komende race zonder inhoud',
        'content' => null,
        'starts_at' => now()->addDays(7),
        'registration_deadline_at' => now()->addDays(6),
        'registration_status' => EventRegistrationStatus::Open,
    ]);
    $withoutCover = Event::factory()->published()->create([
        'season_id' => $season->id,
        'title' => 'Komende race zonder omslagafbeelding',
        'starts_at' => now()->addDays(8),
        'registration_deadline_at' => now()->addDays(7),
        'registration_status' => EventRegistrationStatus::Open,
    ]);
    $expiredRegistration = Event::factory()->published()->withCoverImage()->create([
        'season_id' => $season->id,
        'title' => 'Komende race met verlopen inschrijfdeadline',
        'starts_at' => now()->addDays(9),
        'registration_deadline_at' => now()->subHour(),
        'registration_status' => EventRegistrationStatus::Open,
    ]);
    Event::factory()->published()->withCoverImage()->create([
        'season_id' => $season->id,
        'title' => 'Komende race op de inschrijfdeadline',
        'starts_at' => $referenceTime->addDays(10),
        'registration_deadline_at' => $referenceTime,
        'registration_status' => EventRegistrationStatus::Open,
    ]);
    Event::factory()->published()->withCoverImage()->create([
        'season_id' => $season->id,
        'title' => 'Afgelopen race met verlopen inschrijfdeadline',
        'starts_at' => $referenceTime->subSecond(),
        'registration_deadline_at' => $referenceTime->subDay(),
        'registration_status' => EventRegistrationStatus::Open,
    ]);

    $this->actingAs($admin);

    $this->get(route('admin.events.index', [
        'situation' => ['closed_registration'],
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.situation', ['closed_registration'])
            ->where('situationOptions', [
                ['value' => 'closed_registration', 'label' => 'Registratie gesloten'],
                ['value' => 'expired_registration', 'label' => 'Inschrijfdeadline verlopen'],
                ['value' => 'without_content', 'label' => 'Zonder inhoud'],
                ['value' => 'without_cover', 'label' => 'Zonder omslagafbeelding'],
                ['value' => 'without_season', 'label' => 'Zonder seizoen'],
            ])
            ->has('events.data', 1)
            ->where('events.data.0.id', $closedRegistration->id),
        );

    foreach ([
        'expired_registration' => $expiredRegistration,
        'without_content' => $withoutContent,
        'without_cover' => $withoutCover,
        'without_season' => $withoutSeason,
    ] as $situation => $expectedEvent) {
        $this->get(route('admin.events.index', [
            'situation' => [$situation],
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.situation', [$situation])
                ->has('events.data', 1)
                ->where('events.data.0.id', $expectedEvent->id),
            );
    }
});

test('editors can create and update events but cannot publish or delete them', function () {
    $editor = User::factory()->create();
    $editor->assignRole(Role::Editor->value);
    $location = Location::factory()->create();

    $this->actingAs($editor)
        ->post(route('admin.events.store'), validEventPayload($location, [
            'title' => 'Editor training',
            'slug' => '',
        ]))
        ->assertRedirect();

    $event = Event::query()->where('slug', 'editor-training')->firstOrFail();

    $this->actingAs($editor)
        ->put(route('admin.events.update', $event), validEventPayload($location, [
            'title' => 'Bijgewerkte editor training',
            'slug' => 'bijgewerkte-editor-training',
        ]))
        ->assertRedirect(route('admin.events.edit', $event));

    expect($event->refresh()->title)->toBe('Bijgewerkte editor training');

    $this->actingAs($editor)
        ->patch(route('admin.events.publish', $event))
        ->assertForbidden();

    $this->actingAs($editor)
        ->delete(route('admin.events.destroy', $event))
        ->assertForbidden();

    $this->assertModelExists($event);
});

test('admins can create events with normalized prices and generated slugs', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $location = Location::factory()->create();
    $season = Season::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.events.store'), validEventPayload($location, [
            'season_id' => $season->id,
            'title' => 'Indoor Training Oktober',
            'slug' => '',
            'price_euros' => '12.50',
        ]))
        ->assertRedirect();

    $event = Event::query()->where('slug', 'indoor-training-oktober')->firstOrFail();

    expect($event)
        ->title->toBe('Indoor Training Oktober')
        ->season_id->toBe($season->id)
        ->price_cents->toBe(1250)
        ->created_by->toBe($admin->id)
        ->updated_by->toBe($admin->id)
        ->status->toBe(EventStatus::Draft)
        ->type->toBe(EventType::Training)
        ->registration_status->toBe(EventRegistrationStatus::Open);
});

test('event activity shows manual editors and system or import records', function () {
    $systemEvent = Event::factory()->create(['title' => 'Geïmporteerd event']);
    $admin = User::factory()->create(['name' => 'Ada Admin']);
    $admin->assignRole(Role::Admin->value);
    $editor = User::factory()->create(['name' => 'Evi Editor']);
    $editor->assignRole(Role::Editor->value);
    $location = Location::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.events.store'), validEventPayload($location, [
            'title' => 'Handmatig event',
            'slug' => 'handmatig-event',
        ]))
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Event aangemaakt als concept.',
        ]);

    $event = Event::query()->where('slug', 'handmatig-event')->firstOrFail();

    $this->actingAs($editor)
        ->put(route('admin.events.update', $event), validEventPayload($location, [
            'title' => 'Bewerkt event',
            'slug' => 'handmatig-event',
        ]))
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Event opgeslagen.',
        ]);

    expect($event->refresh())
        ->created_by->toBe($admin->id)
        ->updated_by->toBe($editor->id)
        ->and($systemEvent->refresh())
        ->created_by->toBeNull()
        ->updated_by->toBeNull();

    $this->get(route('admin.events.edit', $event))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('event.activity.createdBy.id', $admin->id)
            ->where('event.activity.createdBy.name', 'Ada Admin')
            ->where('event.activity.updatedBy.id', $editor->id)
            ->where('event.activity.updatedBy.name', 'Evi Editor')
            ->has('event.activity.createdAt')
            ->has('event.activity.updatedAt'),
        );

    $this->get(route('admin.events.index', ['search' => 'Bewerkt event']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('events.data', 1)
            ->where('events.data.0.activity.updatedBy.id', $editor->id)
            ->where('events.data.0.activity.updatedBy.name', 'Evi Editor')
            ->has('events.data.0.activity.updatedAt'),
        );

    $this->get(route('admin.events.edit', $systemEvent))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('event.activity.createdBy', null)
            ->where('event.activity.updatedBy', null),
        );
});

test('event requests reject invalid chronology and references', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $location = Location::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.events.store'), validEventPayload($location, [
            'location_id' => 999999,
            'ends_at' => '2026-09-01T09:00',
            'starts_at' => '2026-09-01T10:00',
            'registration_opens_at' => '2026-09-01T09:30',
            'registration_deadline_at' => '2026-09-01T09:00',
            'registration_url' => 'javascript:alert(1)',
        ]))
        ->assertSessionHasErrors([
            'location_id',
            'ends_at',
            'registration_opens_at',
            'registration_url',
        ]);
});

test('admins can publish cancel unpublish and remove events with public visibility following status', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $event = Event::factory()->create();

    $this->actingAs($admin)
        ->patch(route('admin.events.cancel', $event))
        ->assertForbidden();

    $this->actingAs($admin)
        ->patch(route('admin.events.publish', $event))
        ->assertRedirect()
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Event gepubliceerd.',
        ]);

    expect($event->refresh())
        ->status->toBe(EventStatus::Published)
        ->updated_by->toBe($admin->id)
        ->published_at->not->toBeNull();

    $this->get(route('events.show', $event->slug))->assertOk();

    $this->actingAs($admin)
        ->patch(route('admin.events.cancel', $event))
        ->assertRedirect()
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Event geannuleerd.',
        ]);

    expect($event->refresh()->status)->toBe(EventStatus::Cancelled);
    $this->get(route('events.show', $event->slug))->assertOk();

    $this->actingAs($admin)
        ->patch(route('admin.events.unpublish', $event))
        ->assertRedirect()
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Publicatie ingetrokken.',
        ]);

    expect($event->refresh())
        ->status->toBe(EventStatus::Draft)
        ->published_at->toBeNull();

    $this->get(route('events.show', $event->slug))->assertNotFound();

    $this->actingAs($admin)
        ->delete(route('admin.events.destroy', $event))
        ->assertRedirect(route('admin.events.index'))
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Event verwijderd.',
        ]);

    $this->assertModelMissing($event);
});

/** @param array<string, mixed> $overrides */
function validEventPayload(Location $location, array $overrides = []): array
{
    return [
        'location_id' => $location->id,
        'season_id' => null,
        'title' => 'Trainingavond',
        'slug' => 'trainingavond',
        'content' => 'Neem je racequad en videobril mee.',
        'starts_at' => '2026-10-15T18:00',
        'ends_at' => '2026-10-15T22:00',
        'type' => EventType::Training->value,
        'price_euros' => '10.00',
        'capacity' => 16,
        'registration_opens_at' => '2026-09-15T10:00',
        'registration_deadline_at' => '2026-10-14T23:59',
        'registration_status' => EventRegistrationStatus::Open->value,
        'registration_url' => 'https://example.com/inschrijven',
        ...$overrides,
    ];
}
