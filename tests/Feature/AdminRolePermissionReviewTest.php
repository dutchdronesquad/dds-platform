<?php

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission as PermissionModel;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('only admins can review roles and permissions', function () {
    $editor = User::factory()->create();
    $editor->assignRole(Role::Editor->value);

    $this->get(route('admin.roles.index'))->assertRedirect(route('login'));

    $this->actingAs($editor)
        ->get(route('admin.roles.index'))
        ->assertForbidden();
});

test('admins can review the synchronized code owned access model', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    User::factory()->create()->assignRole(Role::Editor->value);

    $adminPermissions = collect(Permission::cases())
        ->map(fn (Permission $permission): string => $permission->value)
        ->sort()
        ->values()
        ->all();
    $editorPermissions = collect(Role::Editor->permissions())
        ->map(fn (Permission $permission): string => $permission->value)
        ->sort()
        ->values()
        ->all();

    $this->actingAs($admin)
        ->get(route('admin.roles.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/roles/index')
            ->has('roles', 2)
            ->where('roles.0.name', Role::Admin->value)
            ->where('roles.0.userCount', 1)
            ->where('roles.0.permissions', $adminPermissions)
            ->where('roles.0.isSynchronized', true)
            ->where('roles.1.name', Role::Editor->value)
            ->where('roles.1.userCount', 1)
            ->where('roles.1.permissions', $editorPermissions)
            ->where('roles.1.isSynchronized', true)
            ->has('permissionGroups', 4)
            ->where('synchronization.isSynchronized', true)
            ->where('synchronization.missingRoles', [])
            ->where('synchronization.missingPermissions', [])
            ->where('synchronization.unexpectedRoles', [])
            ->where('synchronization.unexpectedPermissions', []),
        );
});

test('the review exposes database drift from the permission enums', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    PermissionModel::findOrCreate('legacy.manage', 'web');

    $this->actingAs($admin)
        ->get(route('admin.roles.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('synchronization.isSynchronized', false)
            ->where('synchronization.unexpectedPermissions', ['legacy.manage']),
        );
});
