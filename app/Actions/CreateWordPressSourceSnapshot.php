<?php

namespace App\Actions;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;
use JsonException;
use RuntimeException;
use Throwable;

final class CreateWordPressSourceSnapshot
{
    /**
     * @return array{directory: string, posts: int, pages: int, media: int, media_files: int, bytes: int}
     *
     * @throws JsonException
     */
    public function handle(
        string $manifestPath,
        string $outputDirectory,
        string $xmlPath,
        bool $force,
    ): array {
        $manifest = $this->readManifest($manifestPath);
        $endpoints = $this->endpoints($manifest);
        $this->validateDestination($outputDirectory, $force);
        $this->validateXml($xmlPath);

        $temporaryDirectory = $outputDirectory.'.building-'.Str::uuid();
        File::ensureDirectoryExists($temporaryDirectory);

        try {
            $collections = [];
            $recordsByType = [];

            foreach ($endpoints as $type => $endpoint) {
                $collection = $this->fetchCollection($endpoint, $type);
                $sourceRecords = $collection['records'];

                if ($type !== 'media' && count($sourceRecords) !== $collection['reported_total']) {
                    throw new RuntimeException(
                        "WordPress REST rapporteert {$collection['reported_total']} {$type}-records maar leverde er ".count($sourceRecords).'.',
                    );
                }

                $records = $this->selectedRecords(
                    $sourceRecords,
                    $this->selectedIds($manifest, $type),
                    $type,
                );
                $relativePath = "{$type}.json";
                $contents = $this->encodedJson($records);
                File::put($temporaryDirectory.DIRECTORY_SEPARATOR.$relativePath, $contents);
                $recordsByType[$type] = $records;
                $collections[$type] = [
                    'path' => $relativePath,
                    'count' => count($records),
                    'source_count' => count($sourceRecords),
                    'source_reported_count' => $collection['reported_total'],
                    'checksum_sha256' => hash('sha256', $contents),
                ];
            }

            $mediaFiles = $this->downloadMedia($recordsByType['media'], $temporaryDirectory);

            if (count($mediaFiles) !== $collections['media']['count']) {
                throw new RuntimeException(
                    "De bronbundel verwacht {$collections['media']['count']} geselecteerde mediabestanden maar bevat er ".count($mediaFiles).'.',
                );
            }

            $xmlContents = File::get($xmlPath);
            $xmlFilename = 'wordpress-export.xml';
            File::put($temporaryDirectory.DIRECTORY_SEPARATOR.$xmlFilename, $xmlContents);

            $snapshot = [
                'schema_version' => 1,
                'captured_at' => now()->toIso8601String(),
                'source' => $endpoints,
                'xml' => [
                    'path' => $xmlFilename,
                    'size_bytes' => strlen($xmlContents),
                    'checksum_sha256' => hash('sha256', $xmlContents),
                ],
                'collections' => $collections,
                'media_files' => $mediaFiles,
            ];

            File::put(
                $temporaryDirectory.DIRECTORY_SEPARATOR.'snapshot.json',
                $this->encodedJson($snapshot),
            );

            if ($force && File::isDirectory($outputDirectory)) {
                File::deleteDirectory($outputDirectory);
            }

            if (! File::moveDirectory($temporaryDirectory, $outputDirectory)) {
                throw new RuntimeException('Kon de complete WordPress bronbundel niet activeren.');
            }

            Arr::set(
                $manifest,
                'source.snapshot_directory',
                $this->portablePath($outputDirectory),
            );
            File::replace($manifestPath, $this->encodedJson($manifest));

            return [
                'directory' => $outputDirectory,
                'posts' => count($recordsByType['posts']),
                'pages' => count($recordsByType['pages']),
                'media' => count($recordsByType['media']),
                'media_files' => count($mediaFiles),
                'bytes' => (int) collect($mediaFiles)->sum('size_bytes'),
            ];
        } catch (Throwable $exception) {
            File::deleteDirectory($temporaryDirectory);

            throw $exception;
        }
    }

    /** @return array<string, mixed> */
    private function readManifest(string $manifestPath): array
    {
        if (! File::isFile($manifestPath)) {
            throw new InvalidArgumentException("Manifest niet gevonden: {$manifestPath}");
        }

        $manifest = json_decode(File::get($manifestPath), true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($manifest)) {
            throw new InvalidArgumentException('Het WordPress-manifest moet een JSON-object bevatten.');
        }

        return $manifest;
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @return array{posts: string, pages: string, media: string}
     */
    private function endpoints(array $manifest): array
    {
        $endpoints = [];

        foreach (['posts', 'pages', 'media'] as $type) {
            $endpoint = Arr::get($manifest, "source.{$type}_endpoint");

            if (! is_string($endpoint)
                || filter_var($endpoint, FILTER_VALIDATE_URL) === false
                || ! in_array(parse_url($endpoint, PHP_URL_SCHEME), ['http', 'https'], true)) {
                throw new InvalidArgumentException(
                    "Het manifest mist een geldige HTTP(S)-URL in source.{$type}_endpoint.",
                );
            }

            $endpoints[$type] = rtrim($endpoint, '/');
        }

        return $endpoints;
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @return list<int>
     */
    private function selectedIds(array $manifest, string $type): array
    {
        $selections = Arr::get($manifest, $type);

        if (! is_array($selections) || ! array_is_list($selections)) {
            throw new InvalidArgumentException("Het manifest moet een {$type}-lijst bevatten.");
        }

        $selectedIds = [];
        $seenIds = [];

        foreach ($selections as $index => $selection) {
            $wordpressId = is_array($selection) ? Arr::get($selection, 'wordpress_id') : null;
            $decision = is_array($selection) ? Arr::get($selection, 'decision') : null;

            if (! is_int($wordpressId) || $wordpressId < 1) {
                throw new InvalidArgumentException("{$type}-selectie {$index} mist een geldige wordpress_id.");
            }

            if (isset($seenIds[$wordpressId])) {
                throw new InvalidArgumentException("WordPress {$type}-ID {$wordpressId} staat dubbel in het manifest.");
            }

            $seenIds[$wordpressId] = true;

            if ($type === 'pages' || $decision === 'import') {
                $selectedIds[] = $wordpressId;
            }
        }

        return $selectedIds;
    }

    /**
     * @param  list<array<string, mixed>>  $sourceRecords
     * @param  list<int>  $selectedIds
     * @return list<array<string, mixed>>
     */
    private function selectedRecords(array $sourceRecords, array $selectedIds, string $type): array
    {
        $sourceRecordsById = [];

        foreach ($sourceRecords as $record) {
            $wordpressId = Arr::get($record, 'id');

            if (is_int($wordpressId)) {
                $sourceRecordsById[$wordpressId] = $record;
            }
        }

        $selectedRecords = [];

        foreach ($selectedIds as $wordpressId) {
            $record = $sourceRecordsById[$wordpressId] ?? null;

            if (! is_array($record)) {
                throw new RuntimeException(
                    "Geselecteerd WordPress {$type}-record {$wordpressId} ontbreekt in de publieke REST-bron.",
                );
            }

            $selectedRecords[] = $record;
        }

        return $selectedRecords;
    }

    /** @return array{records: list<array<string, mixed>>, reported_total: int} */
    private function fetchCollection(string $endpoint, string $type): array
    {
        $records = [];
        $page = 1;
        $totalPages = 1;
        $expectedTotal = null;

        do {
            try {
                $response = Http::acceptJson()
                    ->connectTimeout(5)
                    ->timeout(30)
                    ->retry([100, 250], throw: false)
                    ->get($endpoint, [
                        'per_page' => 100,
                        'page' => $page,
                        'context' => 'view',
                        'orderby' => 'id',
                        'order' => 'asc',
                    ]);
            } catch (ConnectionException $exception) {
                throw new RuntimeException(
                    "WordPress REST-verbinding voor {$type} mislukt: {$exception->getMessage()}",
                );
            }

            if ($response->failed()) {
                throw new RuntimeException(
                    "WordPress REST-aanvraag voor {$type} mislukt met HTTP {$response->status()}.",
                );
            }

            $pageRecords = $response->json();

            if (! is_array($pageRecords) || ! array_is_list($pageRecords)) {
                throw new RuntimeException("WordPress REST gaf geen geldige {$type}-inventaris terug.");
            }

            $records = [...$records, ...$pageRecords];
            $totalPages = max(1, (int) $response->header('X-WP-TotalPages'));
            $totalHeader = $response->header('X-WP-Total');

            if ($page === 1 && $totalHeader !== '' && ctype_digit($totalHeader)) {
                $expectedTotal = (int) $totalHeader;
            }

            $page++;
        } while ($page <= $totalPages);

        $ids = collect($records)->pluck('id');

        if ($ids->contains(fn (mixed $id): bool => ! is_int($id) || $id < 1)
            || $ids->unique()->count() !== $ids->count()) {
            throw new RuntimeException("WordPress REST gaf ongeldige of dubbele {$type}-ID’s terug.");
        }

        return [
            'records' => $records,
            'reported_total' => $expectedTotal ?? count($records),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $mediaRecords
     * @return array<int, array{path: string, source_url: string, mime_type: string, size_bytes: int, checksum_sha256: string}>
     */
    private function downloadMedia(array $mediaRecords, string $temporaryDirectory): array
    {
        $mediaFiles = [];
        $maximumFileSize = (int) config('media-library.max_file_size', 20 * 1024 * 1024);
        File::ensureDirectoryExists($temporaryDirectory.DIRECTORY_SEPARATOR.'media');

        foreach ($mediaRecords as $record) {
            $wordpressId = Arr::get($record, 'id');
            $sourceUrl = Arr::get($record, 'source_url');
            $mimeType = Arr::get($record, 'mime_type');

            if (! is_int($wordpressId)
                || ! is_string($sourceUrl)
                || filter_var($sourceUrl, FILTER_VALIDATE_URL) === false
                || ! in_array(parse_url($sourceUrl, PHP_URL_SCHEME), ['http', 'https'], true)) {
                throw new RuntimeException('WordPress REST bevat een ongeldig mediarecord.');
            }

            try {
                $response = Http::connectTimeout(10)
                    ->timeout(90)
                    ->retry([250, 750], throw: false)
                    ->get($sourceUrl);
            } catch (ConnectionException $exception) {
                throw new RuntimeException(
                    "Downloadverbinding voor media {$wordpressId} mislukt: {$exception->getMessage()}",
                );
            }

            if ($response->failed()) {
                throw new RuntimeException(
                    "Download van media {$wordpressId} mislukt met HTTP {$response->status()}.",
                );
            }

            $contents = $response->body();

            if ($contents === '') {
                throw new RuntimeException("Media {$wordpressId} is leeg.");
            }

            if (strlen($contents) > $maximumFileSize) {
                throw new RuntimeException(
                    "Media {$wordpressId} is groter dan de toegestane {$maximumFileSize} bytes.",
                );
            }

            $detectedMimeType = (new \finfo(FILEINFO_MIME_TYPE))->buffer($contents);

            if (! is_string($detectedMimeType) || $detectedMimeType === '') {
                throw new RuntimeException("Kon het MIME-type van media {$wordpressId} niet bepalen.");
            }

            $filename = $this->filename($record, $sourceUrl);
            $relativePath = "media/{$wordpressId}-{$filename}";
            $path = $temporaryDirectory.DIRECTORY_SEPARATOR.$relativePath;
            File::put($path, $contents);
            $mediaFiles[(string) $wordpressId] = [
                'path' => $relativePath,
                'source_url' => $sourceUrl,
                'mime_type' => is_string($mimeType) && $mimeType !== '' ? $mimeType : $detectedMimeType,
                'size_bytes' => strlen($contents),
                'checksum_sha256' => hash('sha256', $contents),
            ];
        }

        return $mediaFiles;
    }

    /** @param array<string, mixed> $record */
    private function filename(array $record, string $sourceUrl): string
    {
        $wordpressPath = Arr::get($record, 'media_details.file');
        $path = is_string($wordpressPath) && $wordpressPath !== ''
            ? $wordpressPath
            : parse_url($sourceUrl, PHP_URL_PATH);
        $filename = is_string($path)
            ? Str::of(rawurldecode($path))->afterLast('/')->replaceMatches('/[^A-Za-z0-9._-]/', '-')->trim('-')->toString()
            : '';

        return $filename !== '' ? $filename : "wordpress-media-{$record['id']}";
    }

    private function validateDestination(string $outputDirectory, bool $force): void
    {
        if ($outputDirectory === '' || $outputDirectory === base_path()) {
            throw new InvalidArgumentException('Kies een specifieke map voor de WordPress bronbundel.');
        }

        if (File::exists($outputDirectory) && ! $force) {
            throw new InvalidArgumentException(
                "Bronbundelmap bestaat al: {$outputDirectory}. Gebruik --force om deze exact te vervangen.",
            );
        }
    }

    private function validateXml(string $xmlPath): void
    {
        if (! File::isFile($xmlPath) || Str::lower(pathinfo($xmlPath, PATHINFO_EXTENSION)) !== 'xml') {
            throw new InvalidArgumentException("Geldige WordPress XML-export niet gevonden: {$xmlPath}");
        }
    }

    private function portablePath(string $path): string
    {
        $basePath = rtrim(base_path(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        return str_starts_with($path, $basePath) ? Str::after($path, $basePath) : $path;
    }

    /** @param array<mixed> $value */
    private function encodedJson(array $value): string
    {
        return json_encode(
            $value,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        ).PHP_EOL;
    }
}
