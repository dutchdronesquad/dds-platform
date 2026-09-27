<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\TrackDrawConnection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Throwable;

final class EventTrackProjectController extends Controller
{
    public function __invoke(Request $request, Event $event): JsonResponse
    {
        Gate::authorize('update', $event);
        $validated = $request->validate([
            'connection_id' => ['required', 'integer', 'exists:track_draw_connections,id'],
            'cursor' => ['nullable', 'string', 'max:1024'],
        ]);
        $connection = TrackDrawConnection::query()->whereKey($validated['connection_id'])->firstOrFail();

        try {
            $response = Http::withToken($connection->api_key)
                ->acceptJson()->withoutRedirecting()->connectTimeout(3)->timeout(10)
                ->get(config('services.trackdraw.url').'/api/v1/projects', [
                    'limit' => 100,
                    'cursor' => $validated['cursor'] ?? null,
                ]);
            $payload = $response->json();
            if (! $response->successful() || ! is_array($payload)) {
                throw new \UnexpectedValueException('Invalid project response.');
            }
            Validator::make($payload, [
                'data' => ['present', 'array', 'list', 'max:100'],
                'data.*.id' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_-]+$/'],
                'data.*.title' => ['required', 'string', 'max:1000'],
                'pagination.has_more' => ['required', 'boolean'],
                'pagination.next_cursor' => ['present', 'nullable', 'string', 'max:1024', 'required_if:pagination.has_more,true'],
            ])->validate();

            return response()->json([
                'projects' => array_map(fn (array $project): array => [
                    'id' => $project['id'],
                    'title' => $project['title'],
                ], $payload['data']),
                'nextCursor' => $payload['pagination']['has_more'] ? $payload['pagination']['next_cursor'] : null,
            ])->header('Cache-Control', 'private, no-store');
        } catch (Throwable) {
            return response()->json([
                'message' => 'De cloudprojecten konden niet worden opgehaald. Controleer de TrackDraw-koppeling en probeer opnieuw.',
            ], 502)->header('Cache-Control', 'private, no-store');
        }
    }
}
