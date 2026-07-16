<?php

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('it creates a verified admin user', function () {
    $this->artisan('dds:make-admin', [
        'email' => 'admin@example.com',
        '--name' => 'DDS Admin',
        '--password' => 'password',
    ])->assertSuccessful();

    $user = User::query()->where('email', 'admin@example.com')->firstOrFail();

    expect($user)
        ->name->toBe('DDS Admin')
        ->email_verified_at->not->toBeNull()
        ->and(Hash::check('password', $user->password))->toBeTrue()
        ->and($user->hasRole(Role::Admin->value))->toBeTrue()
        ->and($user->can(Permission::DeleteEvents->value))->toBeTrue()
        ->and($user->can(Permission::ViewUsers->value))->toBeTrue();
});

test('it generates a password when creating a new admin without a password option', function () {
    $this->artisan('dds:make-admin', [
        'email' => 'generated@example.com',
        '--name' => 'Generated Admin',
    ])
        ->expectsOutputToContain('Generated password:')
        ->expectsOutput('Store this password now. It will not be shown again.')
        ->assertSuccessful();

    $user = User::query()->where('email', 'generated@example.com')->firstOrFail();

    expect($user)
        ->email_verified_at->not->toBeNull()
        ->and($user->password)->not->toBeNull()
        ->and($user->hasRole(Role::Admin->value))->toBeTrue();
});

test('it promotes an existing user to admin without changing the password', function () {
    $user = User::factory()->unverified()->create([
        'email' => 'editor@example.com',
        'name' => 'Existing User',
        'password' => 'current-password',
    ]);

    $this->artisan('dds:make-admin', [
        'email' => 'editor@example.com',
    ])->assertSuccessful();

    $user->refresh();

    expect($user)
        ->name->toBe('Existing User')
        ->email_verified_at->not->toBeNull()
        ->and(Hash::check('current-password', $user->password))->toBeTrue()
        ->and($user->hasRole(Role::Admin->value))->toBeTrue();
});

test('it rejects invalid admin details without creating a user', function () {
    $this->artisan('dds:make-admin', [
        'email' => 'not-an-email',
        '--name' => 'Invalid Admin',
        '--password' => 'password',
    ])->assertFailed();

    $this->assertDatabaseMissing('users', [
        'email' => 'not-an-email',
    ]);
});
