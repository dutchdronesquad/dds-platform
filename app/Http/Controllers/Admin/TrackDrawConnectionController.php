<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrackDrawConnection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class TrackDrawConnectionController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('manage', TrackDrawConnection::class);

        return Inertia::render('admin/trackdraw', [
            'apiKeysUrl' => config()->string('services.trackdraw.url').'/dashboard/api-keys',
            'connections' => TrackDrawConnection::query()->select(['id', 'name', 'updated_at'])->withCount('events')->orderBy('name')->orderBy('id')->paginate(15)->withQueryString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manage', TrackDrawConnection::class);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'api_key' => ['required', 'string', 'max:1000'],
        ]);
        TrackDrawConnection::query()->create($validated);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'TrackDraw-koppeling toegevoegd.']);

        return back();
    }

    public function update(Request $request, TrackDrawConnection $connection): RedirectResponse
    {
        Gate::authorize('manage', TrackDrawConnection::class);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'api_key' => ['nullable', 'string', 'max:1000'],
        ]);
        if (empty($validated['api_key'])) {
            unset($validated['api_key']);
        }
        $connection->update($validated);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'TrackDraw-koppeling bijgewerkt.']);

        return back();
    }

    public function destroy(TrackDrawConnection $connection): RedirectResponse
    {
        Gate::authorize('manage', TrackDrawConnection::class);
        $connection->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Koppeling verwijderd. Opgeslagen banen blijven beschikbaar.']);

        return back();
    }
}
