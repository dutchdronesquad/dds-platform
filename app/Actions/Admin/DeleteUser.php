<?php

namespace App\Actions\Admin;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteUser
{
    public function handle(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);

            if ($lockedUser->is_active) {
                throw ValidationException::withMessages([
                    'account' => 'Blokkeer dit account voordat je het verwijdert.',
                ]);
            }

            DB::table((string) config('session.table', 'sessions'))
                ->where('user_id', $lockedUser->id)
                ->delete();

            DB::table('password_reset_tokens')
                ->where('email', $lockedUser->email)
                ->delete();

            $lockedUser->delete();
        }, attempts: 3);
    }
}
