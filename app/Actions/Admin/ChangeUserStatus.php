<?php

namespace App\Actions\Admin;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role as RoleModel;

class ChangeUserStatus
{
    public function handle(User $user, bool $isActive): User
    {
        return DB::transaction(function () use ($user, $isActive): User {
            RoleModel::query()
                ->where('name', Role::Admin->value)
                ->where('guard_name', 'web')
                ->lockForUpdate()
                ->firstOrFail();

            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);

            if (! $isActive
                && $lockedUser->is_active
                && $lockedUser->hasRole(Role::Admin->value)
                && User::query()->where('is_active', true)->role(Role::Admin->value)->count() <= 1) {
                throw ValidationException::withMessages([
                    'account' => 'De laatste actieve beheerder kan niet worden geblokkeerd.',
                ]);
            }

            $lockedUser->is_active = $isActive;
            $lockedUser->save();

            if (! $isActive) {
                DB::table((string) config('session.table', 'sessions'))
                    ->where('user_id', $lockedUser->id)
                    ->delete();
            }

            return $lockedUser->refresh();
        }, attempts: 3);
    }
}
