<?php

namespace App\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use JsonException;
use RuntimeException;

final class WordPressSourceRepository
{
    /** @var array<string, array<string, mixed>> */
    private array $snapshots = [];

    /**
     * @param  array<string, mixed>  $manifest
     * @return array<string, mixed>|string
     */
    public function record(array $manifest, string $type, string $endpoint, int $wordpressId): array|string
    {
        if ($this->snapshotDirectory($manifest) !== null) {
            try {
                $record = collect($this->recordsFromSnapshot($manifest, $type))
                    ->first(fn (array $candidate): bool => Arr::get($candidate, 'id') === $wordpressId);
            } catch (JsonException|RuntimeException $exception) {
                return $exception->getMessage();
            }

            return $record === null
                ? "WordPress bronbundel bevat geen {$type}-record met ID {$wordpressId}."
                : $record;
        }

        try {
            $response = Http::acceptJson()
                ->connectTimeout(5)
                ->timeout(15)
                ->retry([100, 250], throw: false)
                ->get("{$endpoint}/{$wordpressId}");
        } catch (ConnectionException $exception) {
            return "WordPress REST-verbinding mislukt: {$exception->getMessage()}";
        }

        if ($response->failed()) {
            return "WordPress REST-aanvraag mislukt met HTTP {$response->status()}.";
        }

        $record = $response->json();

        return is_array($record)
            ? $record
            : "WordPress REST gaf geen geldig {$type}-record terug.";
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @return list<array<string, mixed>>
     */
    public function records(array $manifest, string $type, string $endpoint): array
    {
        if ($this->snapshotDirectory($manifest) !== null) {
            return $this->recordsFromSnapshot($manifest, $type);
        }

        try {
            $response = Http::acceptJson()
                ->connectTimeout(5)
                ->timeout(30)
                ->retry([100, 250], throw: false)
                ->get($endpoint, [
                    'per_page' => 100,
                    'status' => 'publish',
                    '_fields' => 'id,slug,title,link,status,featured_media,content',
                ]);
        } catch (ConnectionException $exception) {
            throw new RuntimeException(
                "WordPress REST-verbinding mislukt: {$exception->getMessage()}",
            );
        }

        if ($response->failed()) {
            throw new RuntimeException(
                "WordPress REST-aanvraag mislukt met HTTP {$response->status()}.",
            );
        }

        $records = $response->json();

        if (! is_array($records) || ! array_is_list($records)) {
            throw new RuntimeException("WordPress REST gaf geen geldige {$type}-inventaris terug.");
        }

        return $records;
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @return array{contents: string}|array{error: string}
     */
    public function mediaContents(
        array $manifest,
        int $wordpressId,
        string $sourceUrl,
        string $expectedMimeType,
    ): array {
        $snapshotDirectory = $this->snapshotDirectory($manifest);

        if ($snapshotDirectory !== null) {
            try {
                $snapshot = $this->snapshot($snapshotDirectory);
                $relativePath = Arr::get($snapshot, "media_files.{$wordpressId}.path");
                $expectedChecksum = Arr::get($snapshot, "media_files.{$wordpressId}.checksum_sha256");

                if (! is_string($relativePath) || ! is_string($expectedChecksum)) {
                    return ['error' => "WordPress bronbundel mist mediabestand {$wordpressId}."];
                }

                $path = $this->safeBundlePath($snapshotDirectory, $relativePath);

                if (! File::isFile($path)) {
                    return ['error' => "WordPress bronbundelbestand ontbreekt: {$relativePath}."];
                }

                $contents = File::get($path);

                if (! hash_equals($expectedChecksum, hash('sha256', $contents))) {
                    return ['error' => "Checksum van WordPress mediabestand {$wordpressId} klopt niet."];
                }

                return $this->validateMediaContents($contents, $sourceUrl, $expectedMimeType);
            } catch (JsonException|RuntimeException $exception) {
                return ['error' => $exception->getMessage()];
            }
        }

        try {
            $response = Http::connectTimeout(10)
                ->timeout(60)
                ->retry([250, 750], throw: false)
                ->get($sourceUrl);
        } catch (ConnectionException $exception) {
            return ['error' => "Downloadverbinding mislukt: {$exception->getMessage()}"];
        }

        if ($response->failed()) {
            return ['error' => "Download mislukt met HTTP {$response->status()}: {$sourceUrl}"];
        }

        return $this->validateMediaContents($response->body(), $sourceUrl, $expectedMimeType);
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @return list<array<string, mixed>>
     *
     * @throws JsonException
     */
    private function recordsFromSnapshot(array $manifest, string $type): array
    {
        $snapshotDirectory = $this->snapshotDirectory($manifest);

        if ($snapshotDirectory === null) {
            throw new RuntimeException('Het manifest mist source.snapshot_directory.');
        }

        $snapshot = $this->snapshot($snapshotDirectory);
        $relativePath = Arr::get($snapshot, "collections.{$type}.path");
        $expectedChecksum = Arr::get($snapshot, "collections.{$type}.checksum_sha256");

        if (! is_string($relativePath) || ! is_string($expectedChecksum)) {
            throw new RuntimeException("WordPress bronbundel mist de {$type}-inventaris.");
        }

        $path = $this->safeBundlePath($snapshotDirectory, $relativePath);

        if (! File::isFile($path)) {
            throw new RuntimeException("WordPress bronbestand ontbreekt: {$relativePath}.");
        }

        $contents = File::get($path);

        if (! hash_equals($expectedChecksum, hash('sha256', $contents))) {
            throw new RuntimeException("Checksum van WordPress {$type}-inventaris klopt niet.");
        }

        $records = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($records) || ! array_is_list($records)) {
            throw new RuntimeException("WordPress bronbundel bevat geen geldige {$type}-inventaris.");
        }

        return $records;
    }

    /** @return array<string, mixed> */
    private function snapshot(string $snapshotDirectory): array
    {
        if (isset($this->snapshots[$snapshotDirectory])) {
            return $this->snapshots[$snapshotDirectory];
        }

        $path = $snapshotDirectory.DIRECTORY_SEPARATOR.'snapshot.json';

        if (! File::isFile($path)) {
            throw new RuntimeException("WordPress bronbundelmanifest ontbreekt: {$path}.");
        }

        $snapshot = json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($snapshot) || Arr::get($snapshot, 'schema_version') !== 1) {
            throw new RuntimeException('WordPress bronbundel heeft een onbekende schemaversie.');
        }

        return $this->snapshots[$snapshotDirectory] = $snapshot;
    }

    /** @param array<string, mixed> $manifest */
    private function snapshotDirectory(array $manifest): ?string
    {
        $directory = Arr::get($manifest, 'source.snapshot_directory');

        if (! is_string($directory) || trim($directory) === '') {
            return null;
        }

        return str_starts_with($directory, DIRECTORY_SEPARATOR)
            ? rtrim($directory, DIRECTORY_SEPARATOR)
            : rtrim(base_path($directory), DIRECTORY_SEPARATOR);
    }

    private function safeBundlePath(string $snapshotDirectory, string $relativePath): string
    {
        if ($relativePath === ''
            || str_starts_with($relativePath, DIRECTORY_SEPARATOR)
            || str_contains($relativePath, '..')) {
            throw new RuntimeException('WordPress bronbundel bevat een onveilig relatief pad.');
        }

        return $snapshotDirectory.DIRECTORY_SEPARATOR.$relativePath;
    }

    /** @return array{contents: string}|array{error: string} */
    private function validateMediaContents(
        string $contents,
        string $sourceUrl,
        string $expectedMimeType,
    ): array {
        $maximumSize = (int) config('media-library.max_file_size', 20 * 1024 * 1024);

        if ($contents === '') {
            return ['error' => "Download gaf een leeg bestand terug: {$sourceUrl}"];
        }

        if (strlen($contents) > $maximumSize) {
            return ['error' => "Download is groter dan de toegestane {$maximumSize} bytes: {$sourceUrl}"];
        }

        $detectedMimeType = (new \finfo(FILEINFO_MIME_TYPE))->buffer($contents);

        if ($detectedMimeType !== $expectedMimeType) {
            return [
                'error' => "Gedetecteerd MIME-type {$detectedMimeType} wijkt af van {$expectedMimeType}: {$sourceUrl}",
            ];
        }

        return ['contents' => $contents];
    }
}
