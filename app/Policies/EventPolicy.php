<?php

namespace App\Policies;

use App\Enums\EventStatus;
use App\Enums\Permission;
use App\Enums\Role;
use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ViewEvents->value);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Event $event): bool
    {
        return $user->can(Permission::ViewEvents->value);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can(Permission::CreateEvents->value);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Event $event): bool
    {
        return $user->can(Permission::UpdateEvents->value);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Event $event): bool
    {
        return $user->can(Permission::DeleteEvents->value);
    }

    public function publish(User $user, Event $event): bool
    {
        return $user->hasRole(Role::Admin->value);
    }

    public function cancel(User $user, Event $event): bool
    {
        return $user->hasRole(Role::Admin->value)
            && $event->status === EventStatus::Published;
    }
}
