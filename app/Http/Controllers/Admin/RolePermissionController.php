<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Permission;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role as RoleModel;

final class RolePermissionController extends Controller
{
    public function __invoke(): Response
    {
        Gate::authorize(Permission::ViewRoles->value);

        $storedRoles = RoleModel::query()
            ->where('guard_name', 'web')
            ->with('permissions:id,name')
            ->withCount('users')
            ->get()
            ->keyBy('name');
        $storedPermissions = PermissionModel::query()
            ->where('guard_name', 'web')
            ->pluck('name');

        $roles = collect(Role::cases())
            ->map(function (Role $role) use ($storedRoles): array {
                $storedRole = $storedRoles->get($role->value);
                $assignedPermissions = $storedRole instanceof RoleModel
                    ? $storedRole->permissions->pluck('name')->sort()->values()->all()
                    : [];
                $expectedPermissions = collect($role->permissions())
                    ->map(fn (Permission $permission): string => $permission->value)
                    ->sort()
                    ->values()
                    ->all();

                return [
                    'name' => $role->value,
                    'label' => $role->label(),
                    'description' => $role->description(),
                    'userCount' => $storedRole instanceof RoleModel
                        ? (int) $storedRole->getAttribute('users_count')
                        : 0,
                    'permissions' => $assignedPermissions,
                    'isStored' => $storedRole instanceof RoleModel,
                    'isSynchronized' => $storedRole instanceof RoleModel
                        && $assignedPermissions === $expectedPermissions,
                ];
            })
            ->values();

        $codeRoleNames = collect(Role::cases())->map(fn (Role $role): string => $role->value);
        $codePermissionNames = collect(Permission::cases())->map(fn (Permission $permission): string => $permission->value);
        $missingRoles = $codeRoleNames->diff($storedRoles->keys())->values();
        $missingPermissions = $codePermissionNames->diff($storedPermissions)->values();
        $unexpectedRoles = $storedRoles->keys()->diff($codeRoleNames)->values();
        $unexpectedPermissions = $storedPermissions->diff($codePermissionNames)->values();

        return Inertia::render('admin/roles/index', [
            'roles' => $roles,
            'permissionGroups' => $this->permissionGroups($storedPermissions),
            'synchronization' => [
                'isSynchronized' => $roles->every(fn (array $role): bool => $role['isSynchronized'])
                    && $missingRoles->isEmpty()
                    && $missingPermissions->isEmpty()
                    && $unexpectedRoles->isEmpty()
                    && $unexpectedPermissions->isEmpty(),
                'missingRoles' => $missingRoles,
                'missingPermissions' => $missingPermissions,
                'unexpectedRoles' => $unexpectedRoles,
                'unexpectedPermissions' => $unexpectedPermissions,
            ],
        ]);
    }

    /**
     * @param  Collection<int, string>  $storedPermissions
     * @return list<array{key: string, label: string, permissions: list<array{value: string, label: string, description: string, isStored: bool}>}>
     */
    private function permissionGroups(Collection $storedPermissions): array
    {
        $groups = [];

        foreach (Permission::cases() as $permission) {
            $group = $permission->group();
            $groups[$group] ??= [
                'key' => $group,
                'label' => $permission->groupLabel(),
                'permissions' => [],
            ];
            $groups[$group]['permissions'][] = [
                'value' => $permission->value,
                'label' => $permission->label(),
                'description' => $permission->description(),
                'isStored' => $storedPermissions->contains($permission->value),
            ];
        }

        return array_values($groups);
    }
}
