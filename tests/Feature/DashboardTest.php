<?php

use App\Enums\Role;
use App\Models\Article;
use App\Models\Event;
use App\Models\Redirect;
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
            ->where('resources.projects', true)
            ->where('resources.articles', true)
            ->where('resources.locations', true)
            ->where('resources.partners', true)
            ->where('resources.media', true)
            ->where('resources.users', true)
            ->where('resources.redirects', true)
            ->where('stats.drafts', 0)
            ->where('stats.upcomingEvents', 0)
            ->where('stats.recentActivity', 0)
            ->where('isEmpty', true)
            ->where('management.canViewEvents', true)
            ->where('management.canManageSeasons', true)
            ->where('management.canViewRedirects', true),
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
            ->where('resources.projects', false)
            ->where('resources.articles', false)
            ->where('resources.locations', false)
            ->where('resources.partners', false)
            ->where('resources.media', false)
            ->where('resources.users', false)
            ->where('resources.redirects', true)
            ->where('management.canViewEvents', true)
            ->where('management.canManageSeasons', false)
            ->where('management.canViewRedirects', true),
        );
});

test('dashboard shows operational content counts', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    Event::factory()->count(2)->create();
    Event::factory()->published()->create();
    Article::factory()->create();
    Article::factory()->published()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('stats.drafts', 3)
            ->where('stats.upcomingEvents', 3)
            ->where('stats.recentActivity', 5)
            ->where('isEmpty', false),
        );
});

test('dashboard counts records consistently at its time boundaries', function () {
    $referenceTime = CarbonImmutable::parse('2026-07-17 10:00:00');
    $recentCutoff = $referenceTime->subDays(7);
    CarbonImmutable::setTestNow($referenceTime);

    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    Event::factory()->create([
        'starts_at' => $referenceTime,
        'created_at' => $recentCutoff,
    ]);
    Event::factory()->create([
        'starts_at' => $referenceTime->subSecond(),
        'created_at' => $recentCutoff->subSecond(),
    ]);
    Article::factory()->create(['created_at' => $recentCutoff]);
    Article::factory()->create(['created_at' => $recentCutoff->subSecond()]);
    Redirect::factory()->create(['created_at' => $recentCutoff]);
    Redirect::factory()->create(['created_at' => $recentCutoff->subSecond()]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('stats.upcomingEvents', 1)
            ->where('stats.recentActivity', 3),
        );
});
