<?php

namespace App\Actions;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use JsonException;

final class RecordWordPressImportRun
{
    /**
     * @param  array<string, mixed>  $report
     *
     * @throws JsonException
     */
    public function handle(string $manifestPath, string $phase, array $report): void
    {
        if (! File::isFile($manifestPath)) {
            throw new InvalidArgumentException("Manifest niet gevonden: {$manifestPath}");
        }

        $manifest = json_decode(File::get($manifestPath), true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($manifest)) {
            throw new InvalidArgumentException('Het WordPress-manifest moet een JSON-object bevatten.');
        }

        Arr::set($manifest, "runs.{$phase}", [
            'selected' => (int) Arr::get($report, 'selected', 0),
            'ready' => (int) Arr::get($report, 'ready', 0),
            'imported' => (int) Arr::get($report, 'imported', 0),
            'reused' => (int) Arr::get($report, 'reused', 0),
            'skipped' => (int) Arr::get($report, 'skipped', 0),
            'failed' => (int) Arr::get($report, 'failed', 0),
            'missing_alt_text' => (int) Arr::get($report, 'missing_alt_text', 0),
            'unresolved_links' => (int) Arr::get($report, 'unresolved_links', 0),
            'missing_media' => (int) Arr::get($report, 'missing_media', 0),
            'suspicious_markup' => (int) Arr::get($report, 'suspicious_markup', 0),
            'conflicts' => (int) Arr::get($report, 'conflicts', 0),
            'pending_review' => (int) Arr::get($report, 'pending_review', 0),
            'items' => array_values((array) Arr::get($report, 'items', [])),
            'completed_at' => now()->toIso8601String(),
        ]);

        File::replace(
            $manifestPath,
            json_encode(
                $manifest,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
            ).PHP_EOL,
        );
    }
}
