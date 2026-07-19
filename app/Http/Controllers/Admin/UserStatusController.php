<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\ChangeUserStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class UserStatusController extends Controller
{
    public function block(User $user, ChangeUserStatus $changeUserStatus): RedirectResponse
    {
        Gate::authorize('block', $user);
        $changeUserStatus->handle($user, false);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Account geblokkeerd.']);

        return to_route('admin.users.edit', $user);
    }

    public function unblock(User $user, ChangeUserStatus $changeUserStatus): RedirectResponse
    {
        Gate::authorize('unblock', $user);
        $changeUserStatus->handle($user, true);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Account gedeblokkeerd.']);

        return to_route('admin.users.edit', $user);
    }
}
