<?php

namespace App\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait TracksContentActivity
{
    protected static function bootTracksContentActivity(): void
    {
        static::creating(function (Model $model): void {
            $userId = auth()->id();

            if ($userId === null) {
                return;
            }

            $model->setAttribute('created_by', $userId);
            $model->setAttribute('updated_by', $userId);
        });

        static::updating(function (Model $model): void {
            $userId = auth()->id();

            if ($userId !== null) {
                $model->setAttribute('updated_by', $userId);
            }
        });
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
