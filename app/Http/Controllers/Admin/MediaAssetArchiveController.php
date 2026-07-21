<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaAsset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

final class MediaAssetArchiveController extends Controller
{
    public function archive(MediaAsset $mediaAsset): RedirectResponse
    {
        Gate::authorize('update', $mediaAsset);

        $mediaAsset->update(['archived_at' => now()]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Media-item gearchiveerd.']);

        return back();
    }

    public function restore(MediaAsset $mediaAsset): RedirectResponse
    {
        Gate::authorize('update', $mediaAsset);

        $mediaAsset->update(['archived_at' => null]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Media-item hersteld.']);

        return back();
    }
}
