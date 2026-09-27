<?php

namespace App\Support;

use App\Models\Event;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RuntimeException;

final class TrackDrawSnapshot
{
    private const int MAX_SNAPSHOT_BYTES = 4_000_000;

    /** @return array{title: string, path: string} */
    public function fetch(string $projectId, string $apiKey): array
    {
        $response = Http::withToken($apiKey)->acceptJson()->withoutRedirecting()
            ->connectTimeout(3)->timeout(30)
            ->withOptions(['progress' => static function (int $total, int $downloaded): void {
                if ($total > self::MAX_SNAPSHOT_BYTES || $downloaded > self::MAX_SNAPSHOT_BYTES) {
                    throw new RuntimeException('TrackDraw snapshot exceeds the size limit.');
                }
            }])
            ->get(config()->string('services.trackdraw.url').'/api/v1/projects/'.rawurlencode($projectId).'/viewer-snapshot');

        if (! $response->successful()
            || ! str_starts_with($response->header('Content-Type'), 'application/json')
            || strlen($response->body()) > self::MAX_SNAPSHOT_BYTES) {
            throw new RuntimeException('TrackDraw snapshot unavailable.');
        }

        $data = $response->json('data');
        if (! is_array($data)) {
            throw new RuntimeException('Invalid TrackDraw snapshot.');
        }

        Validator::make($data, [
            'schema' => ['required', 'in:trackdraw.viewer-snapshot.v1'],
            'snapshot_id' => ['required', 'string', 'regex:/^sha256:[a-f0-9]{64}$/'],
            'required_viewer' => ['required', 'array'],
            'required_viewer.schema' => ['required', 'in:trackdraw.viewer-snapshot.v1'],
            'required_viewer.min_renderer_version' => ['required', 'string'],
            'required_viewer.capabilities' => ['present', 'array'],
            'design' => ['required', 'array'],
            'design.version' => ['required', 'integer', 'in:2'],
            'design.title' => ['required', 'string', 'max:2000'],
            'design.field' => ['required', 'array'],
            'design.field.width' => ['required', 'numeric', 'gt:0'],
            'design.field.height' => ['required', 'numeric', 'gt:0'],
            'design.field.origin' => ['required', 'in:tl,bl'],
            'design.field.grid_step' => ['required', 'numeric', 'gt:0'],
            'design.field.ppm' => ['required', 'numeric', 'gt:0'],
            'design.shapes' => ['present', 'array', 'max:5000'],
            'design.updated_at' => ['required', 'string'],
            'assets' => ['present', 'array'],
        ])->validate();

        $snapshot = Arr::only($data, ['schema', 'snapshot_id', 'required_viewer', 'design', 'assets']);
        $path = 'event-tracks/'.Str::uuid().'.json';
        if (! Storage::disk(config()->string('services.trackdraw.disk'))->put($path, json_encode($snapshot, JSON_THROW_ON_ERROR), ['visibility' => 'private', 'ContentType' => 'application/json'])) {
            throw new RuntimeException('Could not store TrackDraw snapshot.');
        }

        return ['title' => $data['design']['title'], 'path' => $path];
    }

    public function deleteUnused(?string $path): void
    {
        if ($path !== null && ! Event::query()->where('trackdraw_snapshot_path', $path)->exists()) {
            Storage::disk(config()->string('services.trackdraw.disk'))->delete($path);
        }
    }
}
