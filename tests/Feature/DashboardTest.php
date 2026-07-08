<?php

use App\Enums\Role as RoleEnum;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->withoutVite();

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Role::findOrCreate(RoleEnum::Admin->value, 'web');
    Role::findOrCreate(RoleEnum::Editor->value, 'web');
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
    $user->assignRole(RoleEnum::Admin->value);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard'),
        );
});

test('editors can visit the dashboard', function () {
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::Editor->value);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard'),
        );
});
