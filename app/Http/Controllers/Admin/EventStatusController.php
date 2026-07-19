<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

final class EventStatusController extends Controller
{
    public function publish(Event $event): RedirectResponse
    {
        Gate::authorize('publish', $event);

        $event->update([
            'status' => EventStatus::Published,
            'published_at' => $event->published_at ?? now(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Event gepubliceerd.']);

        return back();
    }

    public function unpublish(Event $event): RedirectResponse
    {
        Gate::authorize('publish', $event);

        $event->update([
            'status' => EventStatus::Draft,
            'published_at' => null,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Publicatie ingetrokken.']);

        return back();
    }

    public function cancel(Event $event): RedirectResponse
    {
        Gate::authorize('cancel', $event);

        $event->update([
            'status' => EventStatus::Cancelled,
            'published_at' => $event->published_at ?? now(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Event geannuleerd.']);

        return back();
    }
}
