<?php

use App\Enums\EventRegistrationStatus;
use App\Enums\Role;
use App\Models\Event;
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

test('guests are redirected to the login page', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('authenticated users without an admin role are forbidden from the dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertForbidden();
});

test('admins can visit the dashboard', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('resources.events', true)
            ->missing('resources.projects')
            ->where('resources.articles', true)
            ->where('resources.locations', true)
            ->where('resources.partners', true)
            ->where('resources.media', true)
            ->where('resources.users', true)
            ->where('resources.roles', true)
            ->where('resources.redirects', true)
            ->where('capabilities.createEvents', true)
            ->where('capabilities.createSeasons', true)
            ->where('capabilities.viewUsers', true)
            ->where('stats.draftEvents', 0)
            ->where('stats.upcomingEvents', 0)
            ->where('stats.recentChanges', 0)
            ->where('openPoints.draftEvents', 0)
            ->where('openPoints.closedRegistrationEvents', 0)
            ->where('openPoints.expiredRegistrationEvents', 0)
            ->where('openPoints.missingContentEvents', 0)
            ->where('openPoints.missingCoverEvents', 0)
            ->where('openPoints.unassignedUpcomingEvents', 0)
            ->where('nextEvent', null)
            ->where('recentChanges', [])
            ->where('isEmpty', true)
            ->where('management.canViewEvents', true)
            ->where('management.canManageSeasons', true)
            ->where('management.canViewRedirects', true)
            ->where('management.canViewRoles', true),
        );
});

test('editors can visit the dashboard', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Editor->value);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('resources.events', true)
            ->missing('resources.projects')
            ->where('resources.articles', false)
            ->where('resources.locations', false)
            ->where('resources.partners', false)
            ->where('resources.media', false)
            ->where('resources.users', false)
            ->where('resources.roles', false)
            ->where('resources.redirects', true)
            ->where('capabilities.createEvents', true)
            ->where('capabilities.createSeasons', false)
            ->where('capabilities.viewUsers', false)
            ->where('management.canViewEvents', true)
            ->where('management.canManageSeasons', false)
            ->where('management.canViewRedirects', true)
            ->where('management.canViewRoles', false),
        );
});

test('dashboard shows operational open points and recent changes', function () {
    $referenceTime = CarbonImmutable::parse('2026-07-17 10:00:00');
    CarbonImmutable::setTestNow($referenceTime);

    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $actor = User::factory()->create(['name' => 'Eventbeheerder']);
    $season = Season::factory()->create([
        'name' => 'Wintercompetitie',
        'updated_at' => $referenceTime->subMinutes(4),
        'updated_by' => $actor->id,
    ]);

    Event::factory()->create([
        'content' => null,
        'title' => 'Eerstvolgende training',
        'starts_at' => $referenceTime->addDay(),
        'updated_at' => $referenceTime->subMinute(),
        'updated_by' => $actor->id,
    ]);
    Event::factory()->published()->create([
        'title' => 'Race met gesloten registratie',
        'starts_at' => $referenceTime->addDays(2),
        'registration_status' => EventRegistrationStatus::Closed,
        'updated_at' => $referenceTime->subMinutes(2),
        'updated_by' => $actor->id,
    ]);
    Event::factory()->published()->create([
        'season_id' => $season->id,
        'title' => 'Race met open registratie',
        'starts_at' => $referenceTime->addDays(3),
        'registration_deadline_at' => $referenceTime->subHour(),
        'registration_status' => EventRegistrationStatus::Open,
        'updated_at' => $referenceTime->subMinutes(3),
        'updated_by' => $actor->id,
    ]);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('stats.draftEvents', 1)
            ->where('stats.upcomingEvents', 3)
            ->where('stats.recentChanges', 4)
            ->where('openPoints.draftEvents', 1)
            ->where('openPoints.closedRegistrationEvents', 1)
            ->where('openPoints.expiredRegistrationEvents', 1)
            ->where('openPoints.missingContentEvents', 1)
            ->where('openPoints.missingCoverEvents', 3)
            ->where('openPoints.unassignedUpcomingEvents', 2)
            ->where('nextEvent.title', 'Eerstvolgende training')
            ->where('nextEvent.registrationStatus', EventRegistrationStatus::Closed->value)
            ->where('recentChanges.0.title', 'Eerstvolgende training')
            ->where('recentChanges.0.updatedBy.name', 'Eventbeheerder')
            ->where('recentChanges.3.title', 'Wintercompetitie')
            ->where('isEmpty', false),
        );
});

test('editors only receive recent changes for resources they can manage', function () {
    $editor = User::factory()->create();
    $editor->assignRole(Role::Editor->value);

    Event::factory()->create(['title' => 'Editor event']);
    Season::factory()->create(['name' => 'Admin seizoen']);

    $this->actingAs($editor)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('recentChanges', 1)
            ->where('recentChanges.0.kind', 'event')
            ->where('recentChanges.0.title', 'Editor event')
            ->where('stats.recentChanges', 1),
        );
});

test('dashboard counts records consistently at its time boundaries', function () {
    $referenceTime = CarbonImmutable::parse('2026-07-17 10:00:00');
    $recentCutoff = $referenceTime->subDays(7);
    CarbonImmutable::setTestNow($referenceTime);

    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    Event::factory()->create([
        'title' => 'Event op recente grens',
        'starts_at' => $referenceTime,
        'updated_at' => $recentCutoff,
    ]);
    Event::factory()->create([
        'title' => 'Oud event',
        'starts_at' => $referenceTime->subSecond(),
        'updated_at' => $recentCutoff->subSecond(),
    ]);
    Season::factory()->create([
        'name' => 'Seizoen op recente grens',
        'updated_at' => $recentCutoff,
    ]);
    Season::factory()->create([
        'name' => 'Oud seizoen',
        'updated_at' => $recentCutoff->subSecond(),
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('stats.upcomingEvents', 1)
            ->where('stats.recentChanges', 2)
            ->has('recentChanges', 2),
        );
});
