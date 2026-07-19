<?php

namespace App\Actions\Admin;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role as RoleModel;

class UpdateUser
{
    /** @param array{name: string, email: string, locale: string, roles: list<string>} $attributes */
    public function handle(User $user, array $attributes): User
    {
        return DB::transaction(function () use ($user, $attributes): User {
            RoleModel::query()
                ->where('name', Role::Admin->value)
                ->where('guard_name', 'web')
                ->lockForUpdate()
                ->firstOrFail();

            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $willRemainAdmin = in_array(Role::Admin->value, $attributes['roles'], true);

            if ($lockedUser->is_active
                && $lockedUser->hasRole(Role::Admin->value)
                && ! $willRemainAdmin
                && User::query()->where('is_active', true)->role(Role::Admin->value)->count() <= 1) {
                throw ValidationException::withMessages([
                    'roles' => 'De laatste actieve beheerder kan zijn beheerrol niet verliezen.',
                ]);
            }

            $lockedUser->fill(Arr::except($attributes, ['roles']));

            if ($lockedUser->isDirty('email')) {
                $lockedUser->email_verified_at = null;
            }

            $lockedUser->save();
            $lockedUser->syncRoles($attributes['roles']);

            return $lockedUser->refresh()->load('roles');
        }, attempts: 3);
    }
}
