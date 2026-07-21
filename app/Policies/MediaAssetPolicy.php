<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\MediaAsset;
use App\Models\User;

class MediaAssetPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ViewMedia->value);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, MediaAsset $mediaAsset): bool
    {
        return $user->can(Permission::ViewMedia->value);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can(Permission::CreateMedia->value);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, MediaAsset $mediaAsset): bool
    {
        return $user->can(Permission::UpdateMedia->value);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MediaAsset $mediaAsset): bool
    {
        return $user->can(Permission::DeleteMedia->value);
    }
}
