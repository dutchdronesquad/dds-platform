<?php

use App\Enums\Role;
use App\Models\Event;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Vite;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('event editing during hot reload does not request a full page reload because of a production build', function () {
    $editor = User::factory()->create();
    $editor->assignRole(Role::Editor->value);
    $event = Event::factory()->create();
    Vite::partialMock()->shouldReceive('isRunningHot')->andReturnTrue();
    config(['app.asset_url' => 'https://assets.example/new-build']);

    $this->actingAs($editor)->put(route('admin.events.update', $event), [
        'title' => 'Opgeslagen tijdens development',
        'location_id' => $event->location_id,
        'starts_at' => '2026-12-01T10:00:00+01:00',
        'type' => 'training',
        'registration_enabled' => false,
        'registration_closed_manually' => false,
        'registration_full' => false,
        'registration_waitlist_enabled' => false,
    ], ['X-Inertia' => 'true', 'X-Inertia-Version' => ''])
        ->assertSessionHasNoErrors()->assertRedirect(route('admin.events.edit', $event));

    $this->actingAs($editor)->get(route('admin.events.edit', $event), [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => '',
    ])->assertOk()->assertHeader('X-Inertia', 'true')->assertJsonPath('version', '');
    expect($event->fresh()->title)->toBe('Opgeslagen tijdens development');
});

test('production asset changes still require a full page reload', function () {
    $editor = User::factory()->create();
    $editor->assignRole(Role::Editor->value);
    $event = Event::factory()->create();
    Vite::partialMock()->shouldReceive('isRunningHot')->andReturnFalse();
    config(['app.asset_url' => 'https://assets.example/new-build']);

    $this->actingAs($editor)->get(route('admin.events.edit', $event), [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => 'previous-build',
    ])->assertConflict()->assertHeader('X-Inertia-Location', route('admin.events.edit', $event));
});
