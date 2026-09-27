<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\TrackDrawConnection;
use App\Support\TrackDrawSnapshot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Throwable;

final class EventTrackController extends Controller
{
    public function update(Request $request, Event $event, TrackDrawSnapshot $snapshots): RedirectResponse
    {
        Gate::authorize('update', $event);
        $validated = $request->validate([
            'connection_id' => ['required', 'integer', 'exists:track_draw_connections,id'],
            'project_id' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_-]+$/'],
        ]);
        $connection = TrackDrawConnection::query()->whereKey($validated['connection_id'])->firstOrFail();

        try {
            $snapshot = $snapshots->fetch($validated['project_id'], $connection->api_key);
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'project_id' => 'De baan kon niet worden opgehaald. Controleer het project, de API-key met tracks:read-toegang en de TrackDraw-verbinding. De bestaande baan is behouden.',
            ]);
        }

        try {
            $previousPath = DB::transaction(function () use ($event, $validated, $snapshot, $connection): ?string {
                $current = Event::query()->lockForUpdate()->findOrFail($event->id);
                $previousPath = $current->trackdraw_snapshot_path;
                $current->forceFill([
                    'track_draw_connection_id' => $connection->id,
                    'trackdraw_project_id' => $validated['project_id'],
                    'trackdraw_snapshot_path' => $snapshot['path'],
                    'trackdraw_title' => $snapshot['title'],
                    'trackdraw_synced_at' => now(),
                ])->saveOrFail();

                return $previousPath;
            });
        } catch (Throwable $exception) {
            $snapshots->deleteUnused($snapshot['path']);
            throw $exception;
        }
        $snapshots->deleteUnused($previousPath);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Baan opgehaald en opgeslagen.']);

        return back();
    }

    public function destroy(Event $event, TrackDrawSnapshot $snapshots): RedirectResponse
    {
        Gate::authorize('update', $event);
        $previousPath = DB::transaction(function () use ($event): ?string {
            $current = Event::query()->lockForUpdate()->findOrFail($event->id);
            $previousPath = $current->trackdraw_snapshot_path;
            $current->forceFill([
                'track_draw_connection_id' => null,
                'trackdraw_project_id' => null,
                'trackdraw_snapshot_path' => null,
                'trackdraw_title' => null,
                'trackdraw_synced_at' => null,
            ])->saveOrFail();

            return $previousPath;
        });
        $snapshots->deleteUnused($previousPath);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Baan losgekoppeld.']);

        return back();
    }
}
