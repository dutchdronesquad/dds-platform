<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class EventTrackController extends Controller
{
    public function __invoke(Request $request, Event $event): StreamedResponse
    {
        abort_unless($event->isPubliclyVisible() || $request->user()?->can('view', $event), 404);
        abort_if($event->trackdraw_snapshot_path === null, 404);
        abort_unless(Storage::disk('s3')->exists($event->trackdraw_snapshot_path), 404);

        return Storage::disk('s3')->download($event->trackdraw_snapshot_path, 'track.json', [
            'Content-Type' => 'application/json',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
