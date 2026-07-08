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

        Role::findOrCreate(RoleEnum::Admin->value, 'web')
            ->syncPermissions($permissions);

        Role::findOrCreate(RoleEnum::Editor->value, 'web')
            ->syncPermissions([
                PermissionEnum::ViewEvents->value,
                PermissionEnum::CreateEvents->value,
                PermissionEnum::UpdateEvents->value,
            ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
