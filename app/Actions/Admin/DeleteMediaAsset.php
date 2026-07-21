<?php

namespace App\Actions\Admin;

use App\Models\MediaAsset;
use Illuminate\Validation\ValidationException;

class DeleteMediaAsset
{
    public function handle(MediaAsset $mediaAsset): void
    {
        if ($mediaAsset->isInUse()) {
            throw ValidationException::withMessages([
                'mediaAsset' => 'Dit bestand is nog in gebruik en kan niet worden verwijderd.',
            ]);
        }

        $mediaAsset->delete();
    }
}
