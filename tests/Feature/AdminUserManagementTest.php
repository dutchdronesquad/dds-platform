<?php

use App\Enums\Role;
use App\Models\Article;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('the fresh user schema contains management and activity fields', function () {
    expect(Schema::hasColumns('users', ['locale', 'is_active']))->toBeTrue()
        ->and(Schema::hasColumn('sessions', 'last_activity'))->toBeTrue()
        ->and(collect(Schema::getIndexes('sessions'))->contains(
            fn (array $index): bool => $index['columns'] === ['user_id', 'last_activity'],
        ))->toBeTrue();
});

test('only admins can manage users', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $editor = User::factory()->create();
    $editor->assignRole(Role::Editor->value);
    $managedUser = User::factory()->create();

    $this->get(route('admin.users.index'))->assertRedirect(route('login'));

    $this->actingAs($editor)
        ->get(route('admin.users.index'))
        ->assertForbidden();

    $this->actingAs($editor)
        ->get(route('admin.users.edit', $managedUser))
        ->assertForbidden();

    $this->actingAs($editor)
        ->put(route('admin.users.update', $managedUser), validUserPayload())
        ->assertForbidden();

    $this->actingAs($editor)
        ->patch(route('admin.users.block', $managedUser))
        ->assertForbidden();

    $managedUser->forceFill(['is_active' => false])->save();

    $this->actingAs($editor)
        ->delete(route('admin.users.destroy', $managedUser))
        ->assertForbidden();

    expect($admin->can('viewAny', User::class))->toBeTrue()
        ->and($editor->can('viewAny', User::class))->toBeFalse();
});

test('admins can review searchable and filterable user activity', function () {
    $admin = User::factory()->create(['name' => 'Beheerder']);
    $admin->assignRole(Role::Admin->value);
    $recentEditor = User::factory()->unverified()->create([
        'name' => 'Racing Redacteur',
        'email' => 'racing@example.com',
    ]);
    $recentEditor->assignRole(Role::Editor->value);
    User::factory()->create(['name' => 'Andere gebruiker']);

    DB::table((string) config('session.table', 'sessions'))->insert([
        'id' => (string) Str::uuid(),
        'user_id' => $recentEditor->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Pest',
        'payload' => 'test',
        'last_activity' => now()->subDay()->timestamp,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.users.index', [
            'search' => 'RACING',
            'role' => 'editor',
            'verification' => 'unverified',
            'account' => 'active',
            'activity' => 'recent',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/users/index')
            ->where('filters.search', 'RACING')
            ->where('filters.role', ['editor'])
            ->where('filters.verification', ['unverified'])
            ->where('filters.account', ['active'])
            ->where('filters.activity', ['recent'])
            ->where('users.total', 1)
            ->where('users.data.0.name', 'Racing Redacteur')
            ->where('users.data.0.roles', ['editor'])
            ->where('users.data.0.isActive', true)
            ->where('users.data.0.emailVerifiedAt', null)
            ->has('users.data.0.lastActiveAt')
            ->where('summary.total', 3)
            ->where('summary.active', 3)
            ->where('summary.admins', 1)
            ->where('summary.unverified', 1)
            ->where('facets.roles.editor', 1),
        );
});

test('admins can open and update a user with Spatie roles', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $managedUser = User::factory()->create([
        'email' => 'old@example.com',
        'locale' => 'en',
    ]);
    $managedUser->assignRole(Role::Editor->value);

    $this->actingAs($admin)
        ->get(route('admin.users.edit', $managedUser))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/users/edit')
            ->where('user.id', $managedUser->id)
            ->where('user.roles', ['editor'])
            ->where('user.isSoleActiveAdmin', false)
            ->where('user.isCurrentUser', false)
            ->where('user.capabilities.block', true)
            ->where('user.capabilities.unblock', false)
            ->where('user.capabilities.delete', false)
            ->has('roleOptions', 2)
            ->has('localeOptions', 2),
        );

    $this->put(route('admin.users.update', $managedUser), validUserPayload([
        'name' => 'Nieuwe naam',
        'email' => 'NEW@example.com',
        'locale' => 'nl',
        'roles' => [Role::Admin->value],
    ]))->assertRedirect(route('admin.users.edit', $managedUser));

    expect($managedUser->refresh())
        ->name->toBe('Nieuwe naam')
        ->email->toBe('new@example.com')
        ->locale->toBe('nl')
        ->email_verified_at->toBeNull()
        ->is_active->toBeTrue()
        ->and($managedUser->hasExactRoles(Role::Admin->value))->toBeTrue();
});

test('the last active admin cannot be demoted', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    $this->actingAs($admin)
        ->from(route('admin.users.edit', $admin))
        ->put(route('admin.users.update', $admin), validUserPayload([
            'roles' => [Role::Editor->value],
        ], $admin))
        ->assertRedirect(route('admin.users.edit', $admin))
        ->assertSessionHasErrors('roles');

    expect($admin->refresh()->is_active)->toBeTrue()
        ->and($admin->hasRole(Role::Admin->value))->toBeTrue();
});

test('an admin can be demoted when another active admin remains', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $otherAdmin = User::factory()->create();
    $otherAdmin->assignRole(Role::Admin->value);

    $this->actingAs($admin)
        ->put(route('admin.users.update', $admin), validUserPayload([
            'roles' => [Role::Editor->value],
        ], $admin))
        ->assertRedirect(route('dashboard'));

    expect($admin->refresh()->hasExactRoles(Role::Editor->value))->toBeTrue()
        ->and($otherAdmin->hasRole(Role::Admin->value))->toBeTrue();
});

test('user updates validate profile access and role values', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $managedUser = User::factory()->create();
    $existingUser = User::factory()->create(['email' => 'used@example.com']);

    $this->actingAs($admin)
        ->put(route('admin.users.update', $managedUser), [
            'name' => '',
            'email' => $existingUser->email,
            'locale' => 'de',
            'roles' => ['owner'],
        ])
        ->assertSessionHasErrors(['name', 'email', 'locale', 'roles.0']);
});

test('admins can block and unblock another user while ending existing sessions', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $managedUser = User::factory()->create();

    DB::table((string) config('session.table', 'sessions'))->insert([
        'id' => (string) Str::uuid(),
        'user_id' => $managedUser->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Pest',
        'payload' => 'test',
        'last_activity' => now()->timestamp,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.users.block', $managedUser))
        ->assertRedirect(route('admin.users.edit', $managedUser));

    expect($managedUser->refresh()->is_active)->toBeFalse()
        ->and(DB::table((string) config('session.table', 'sessions'))
            ->where('user_id', $managedUser->id)
            ->exists())->toBeFalse();

    $this->patch(route('admin.users.unblock', $managedUser))
        ->assertRedirect(route('admin.users.edit', $managedUser));

    expect($managedUser->refresh()->is_active)->toBeTrue();
});

test('admins cannot block or delete their own account', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    $this->actingAs($admin)
        ->patch(route('admin.users.block', $admin))
        ->assertForbidden();

    $admin->forceFill(['is_active' => false]);

    expect($admin->can('delete', $admin))->toBeFalse();
});

test('admins can permanently delete a blocked user but not an active user', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $activeUser = User::factory()->create();

    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $activeUser))
        ->assertForbidden();

    $blockedUser = User::factory()->create(['is_active' => false]);
    $blockedUser->assignRole(Role::Editor->value);
    $article = Article::factory()->for($blockedUser, 'author')->create();

    DB::table((string) config('session.table', 'sessions'))->insert([
        'id' => (string) Str::uuid(),
        'user_id' => $blockedUser->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Pest',
        'payload' => 'test',
        'last_activity' => now()->timestamp,
    ]);
    DB::table('password_reset_tokens')->insert([
        'email' => $blockedUser->email,
        'token' => 'test-token',
        'created_at' => now(),
    ]);

    $this->delete(route('admin.users.destroy', $blockedUser))
        ->assertRedirect(route('admin.users.index'));

    $this->assertModelMissing($blockedUser);

    expect($article->refresh()->author_id)->toBeNull()
        ->and(DB::table((string) config('session.table', 'sessions'))
            ->where('user_id', $blockedUser->id)
            ->exists())->toBeFalse()
        ->and(DB::table('password_reset_tokens')
            ->where('email', $blockedUser->email)
            ->exists())->toBeFalse();
});

test('inactive users cannot authenticate and existing sessions are ended', function () {
    $inactiveUser = User::factory()->create(['is_active' => false]);

    $this->post(route('login.store'), [
        'email' => $inactiveUser->email,
        'password' => 'password',
    ]);

    $this->assertGuest();

    $this->actingAs($inactiveUser)
        ->get(route('home'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('status');

    $this->assertGuest();
});

/** @param array<string, mixed> $overrides */
function validUserPayload(array $overrides = [], ?User $user = null): array
{
    return [
        'name' => $user?->name ?? 'Platformgebruiker',
        'email' => $user?->email ?? 'platform@example.com',
        'locale' => $user?->locale ?? 'en',
        'roles' => [Role::Editor->value],
        ...$overrides,
    ];
}
