<?php

namespace Database\Seeders;

use App\Enums\Permission as PermissionEnum;
use App\Enums\Role as RoleEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect(PermissionEnum::cases())
            ->map(fn (PermissionEnum $permission) => Permission::findOrCreate($permission->value, 'web'));

        foreach (RoleEnum::cases() as $role) {
            Role::findOrCreate($role->value, 'web')
                ->syncPermissions($role === RoleEnum::Admin ? $permissions : $role->permissions());
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
