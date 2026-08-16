<?php

namespace App\Actions;

use App\Models\MediaAsset;
use App\Support\WordPressSourceRepository;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;
use JsonException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

final class ImportWordPressMedia
{
    public function __construct(private readonly WordPressSourceRepository $sourceRepository) {}

    /**
     * @return array{
     *     items: list<array{wordpress_id: int, status: string, message: string}>,
     *     selected: int,
     *     ready: int,
     *     imported: int,
     *     reused: int,
     *     skipped: int,
     *     failed: int,
     *     missing_alt_text: int
     * }
     *
     * @throws JsonException
     */
    public function handle(string $manifestPath, bool $dryRun): array
    {
        $manifest = $this->readManifest($manifestPath);
        $mediaEndpoint = $this->mediaEndpoint($manifest);
        $selections = $this->selections($manifest);
        $report = $this->emptyReport();

        foreach ($selections as $selection) {
            $wordpressId = $selection['wordpress_id'];

            if ($selection['decision'] === 'skip') {
                $report['skipped']++;
                $report['items'][] = [
                    'wordpress_id' => $wordpressId,
                    'status' => 'overgeslagen',
                    'message' => $selection['reason'] ?? 'Expliciet overgeslagen in het manifest.',
                ];

                continue;
            }

            $report['selected']++;
            $existingMapping = Arr::get($manifest, "mappings.media.{$wordpressId}");

            if ($this->mappedMediaAsset($existingMapping) instanceof MediaAsset) {
                $report['reused']++;

                if (Arr::get($existingMapping, 'alt_text') === null
                    && str_starts_with((string) Arr::get($existingMapping, 'mime_type'), 'image/')) {
                    $report['missing_alt_text']++;
                }

                $report['items'][] = [
                    'wordpress_id' => $wordpressId,
                    'status' => 'hergebruikt',
                    'message' => 'Bestaand MediaAsset uit de manifestmapping gebruikt.',
                ];

                continue;
            }

            $sourceRecord = $this->sourceRepository->record(
                $manifest,
                'media',
                $mediaEndpoint,
                $wordpressId,
            );

            if (is_string($sourceRecord)) {
                $this->addFailure($report, $wordpressId, $sourceRecord);

                continue;
            }

            $metadata = $this->sourceMetadata($sourceRecord, $wordpressId);

            if (is_string($metadata)) {
                $this->addFailure($report, $wordpressId, $metadata);

                continue;
            }

            if (str_starts_with($metadata['mime_type'], 'image/') && $metadata['alt_text'] === null) {
                $report['missing_alt_text']++;
            }

            if (! in_array($metadata['mime_type'], MediaAsset::ACCEPTED_MIME_TYPES, true)) {
                $this->addFailure(
                    $report,
                    $wordpressId,
                    "Niet-ondersteund bestandstype: {$metadata['mime_type']}.",
                );

                continue;
            }

            if ($dryRun) {
                $report['ready']++;
                $report['items'][] = [
                    'wordpress_id' => $wordpressId,
                    'status' => 'klaar',
                    'message' => "{$metadata['filename']} ({$metadata['mime_type']}) kan worden geïmporteerd.",
                ];

                continue;
            }

            $download = $this->sourceRepository->mediaContents(
                $manifest,
                $wordpressId,
                $metadata['source_url'],
                $metadata['mime_type'],
            );

            if (isset($download['error'])) {
                $this->addFailure($report, $wordpressId, $download['error']);

                continue;
            }

            try {
                $mediaAsset = $this->storeMediaAsset($download['contents'], $metadata);
            } catch (Throwable $exception) {
                $this->addFailure(
                    $report,
                    $wordpressId,
                    "Opslaan mislukt: {$exception->getMessage()}",
                );

                continue;
            }

            Arr::set($manifest, "mappings.media.{$wordpressId}", [
                'media_asset_id' => $mediaAsset->getKey(),
                'source_url' => $metadata['source_url'],
                'original_filename' => $metadata['filename'],
                'mime_type' => $mediaAsset->mimeType(),
                'size_bytes' => $mediaAsset->sizeBytes(),
                'width' => $mediaAsset->width(),
                'height' => $mediaAsset->height(),
                'alt_text' => $metadata['alt_text'],
                'caption' => $metadata['caption'],
                'checksum_sha256' => hash('sha256', $download['contents']),
                'imported_at' => now()->toIso8601String(),
            ]);
            $this->writeManifest($manifestPath, $manifest);

            $report['imported']++;
            $report['items'][] = [
                'wordpress_id' => $wordpressId,
                'status' => 'geïmporteerd',
                'message' => "MediaAsset {$mediaAsset->getKey()} aangemaakt voor {$metadata['filename']}.",
            ];
        }

        return $report;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws JsonException
     */
    private function readManifest(string $manifestPath): array
    {
        if (! File::isFile($manifestPath)) {
            throw new InvalidArgumentException("Manifest niet gevonden: {$manifestPath}");
        }

        $manifest = json_decode(
            File::get($manifestPath),
            associative: true,
            flags: JSON_THROW_ON_ERROR,
        );

        if (! is_array($manifest)) {
            throw new InvalidArgumentException('Het WordPress-manifest moet een JSON-object bevatten.');
        }

        return $manifest;
    }

    /** @param array<string, mixed> $manifest */
    private function mediaEndpoint(array $manifest): string
    {
        $mediaEndpoint = Arr::get($manifest, 'source.media_endpoint');

        if (! is_string($mediaEndpoint)
            || filter_var($mediaEndpoint, FILTER_VALIDATE_URL) === false
            || ! in_array(parse_url($mediaEndpoint, PHP_URL_SCHEME), ['http', 'https'], true)) {
            throw new InvalidArgumentException(
                'Het manifest mist een geldige HTTP(S)-URL in source.media_endpoint.',
            );
        }

        return rtrim($mediaEndpoint, '/');
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @return list<array{wordpress_id: int, decision: 'import'|'skip', reason: string|null}>
     */
    private function selections(array $manifest): array
    {
        $media = Arr::get($manifest, 'media');

        if (! is_array($media) || ! array_is_list($media)) {
            throw new InvalidArgumentException('Het manifest moet een media-lijst bevatten.');
        }

        $selections = [];
        $wordpressIds = [];

        foreach ($media as $index => $selection) {
            if (! is_array($selection)) {
                throw new InvalidArgumentException("Media-selectie {$index} moet een JSON-object zijn.");
            }

            $wordpressId = Arr::get($selection, 'wordpress_id');
            $decision = Arr::get($selection, 'decision', 'import');
            $reason = Arr::get($selection, 'reason');

            if (! is_int($wordpressId) || $wordpressId < 1) {
                throw new InvalidArgumentException(
                    "Media-selectie {$index} mist een geldige wordpress_id.",
                );
            }

            if (isset($wordpressIds[$wordpressId])) {
                throw new InvalidArgumentException("WordPress media-ID {$wordpressId} staat dubbel in het manifest.");
            }

            if (! in_array($decision, ['import', 'skip'], true)) {
                throw new InvalidArgumentException(
                    "Media-selectie {$wordpressId} heeft een ongeldige decision.",
                );
            }

            if ($reason !== null && ! is_string($reason)) {
                throw new InvalidArgumentException(
                    "Media-selectie {$wordpressId} heeft een ongeldige reason.",
                );
            }

            $wordpressIds[$wordpressId] = true;
            $selections[] = [
                'wordpress_id' => $wordpressId,
                'decision' => $decision,
                'reason' => $reason,
            ];
        }

        return $selections;
    }

    private function mappedMediaAsset(mixed $mapping): ?MediaAsset
    {
        if (! is_array($mapping)) {
            return null;
        }

        $mediaAssetId = Arr::get($mapping, 'media_asset_id');

        if (! is_int($mediaAssetId)) {
            return null;
        }

        $mediaAsset = MediaAsset::query()->with('media')->find($mediaAssetId);

        return $mediaAsset instanceof MediaAsset && $mediaAsset->file() instanceof Media
            ? $mediaAsset
            : null;
    }

    /**
     * @param  array<string, mixed>  $sourceRecord
     * @return array{
     *     source_url: string,
     *     filename: string,
     *     mime_type: string,
     *     alt_text: string|null,
     *     caption: string|null,
     *     width: int|null,
     *     height: int|null
     * }|string
     */
    private function sourceMetadata(array $sourceRecord, int $wordpressId): array|string
    {
        if (Arr::get($sourceRecord, 'id') !== $wordpressId) {
            return 'WordPress REST gaf een record met een onverwacht ID terug.';
        }

        $sourceUrl = Arr::get($sourceRecord, 'source_url');
        $mimeType = Arr::get($sourceRecord, 'mime_type');

        if (! is_string($sourceUrl)
            || filter_var($sourceUrl, FILTER_VALIDATE_URL) === false
            || ! in_array(parse_url($sourceUrl, PHP_URL_SCHEME), ['http', 'https'], true)) {
            return 'WordPress REST gaf geen geldige download-URL terug.';
        }

        if (! is_string($mimeType) || $mimeType === '') {
            return 'WordPress REST gaf geen MIME-type terug.';
        }

        $filename = $this->filename($sourceRecord, $sourceUrl);

        if ($filename === '') {
            return 'WordPress REST gaf geen bruikbare bestandsnaam terug.';
        }

        $altText = Arr::get($sourceRecord, 'alt_text');
        $caption = Arr::get($sourceRecord, 'caption.rendered');
        $width = Arr::get($sourceRecord, 'media_details.width');
        $height = Arr::get($sourceRecord, 'media_details.height');

        return [
            'source_url' => $sourceUrl,
            'filename' => $filename,
            'mime_type' => $mimeType,
            'alt_text' => is_string($altText) && trim($altText) !== ''
                ? $this->plainText($altText)
                : null,
            'caption' => is_string($caption) && trim($caption) !== ''
                ? $this->plainText($caption)
                : null,
            'width' => is_numeric($width) ? (int) $width : null,
            'height' => is_numeric($height) ? (int) $height : null,
        ];
    }

    /** @param array<string, mixed> $sourceRecord */
    private function filename(array $sourceRecord, string $sourceUrl): string
    {
        $wordpressPath = Arr::get($sourceRecord, 'media_details.file');
        $path = is_string($wordpressPath) && $wordpressPath !== ''
            ? $wordpressPath
            : parse_url($sourceUrl, PHP_URL_PATH);

        if (! is_string($path)) {
            return '';
        }

        return Str::of(rawurldecode($path))
            ->afterLast('/')
            ->replaceMatches('/[^A-Za-z0-9._-]/', '-')
            ->trim('-')
            ->toString();
    }

    private function plainText(string $value): string
    {
        return Str::of(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'))
            ->stripTags()
            ->squish()
            ->toString();
    }

    /**
     * @param array{
     *     source_url: string,
     *     filename: string,
     *     mime_type: string,
     *     alt_text: string|null,
     *     caption: string|null,
     *     width: int|null,
     *     height: int|null
     * } $metadata
     */
    private function storeMediaAsset(string $contents, array $metadata): MediaAsset
    {
        $temporaryPath = tempnam(sys_get_temp_dir(), 'dds-wordpress-media-');

        if (! is_string($temporaryPath)) {
            throw new InvalidArgumentException('Kon geen tijdelijk bestand voor de media-import maken.');
        }

        try {
            File::put($temporaryPath, $contents);

            return DB::transaction(function () use ($temporaryPath, $metadata): MediaAsset {
                $mediaAsset = MediaAsset::query()->create([
                    'alt_text' => $metadata['alt_text'] === null
                        ? null
                        : ['nl' => $metadata['alt_text']],
                ]);

                $mediaAsset
                    ->addMedia($temporaryPath)
                    ->usingName($metadata['filename'])
                    ->usingFileName($metadata['filename'])
                    ->withCustomProperties([
                        'width' => $metadata['width'],
                        'height' => $metadata['height'],
                    ])
                    ->toMediaCollection(MediaAsset::COLLECTION);

                return $mediaAsset->load('media');
            });
        } finally {
            File::delete($temporaryPath);
        }
    }

    /**
     * @param  array<string, mixed>  $manifest
     *
     * @throws JsonException
     */
    private function writeManifest(string $manifestPath, array $manifest): void
    {
        File::replace(
            $manifestPath,
            json_encode(
                $manifest,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
            ).PHP_EOL,
        );
    }

    /**
     * @return array{
     *     items: list<array{wordpress_id: int, status: string, message: string}>,
     *     selected: int,
     *     ready: int,
     *     imported: int,
     *     reused: int,
     *     skipped: int,
     *     failed: int,
     *     missing_alt_text: int
     * }
     */
    private function emptyReport(): array
    {
        return [
            'items' => [],
            'selected' => 0,
            'ready' => 0,
            'imported' => 0,
            'reused' => 0,
            'skipped' => 0,
            'failed' => 0,
            'missing_alt_text' => 0,
        ];
    }

    /**
     * @param array{
     *     items: list<array{wordpress_id: int, status: string, message: string}>,
     *     selected: int,
     *     ready: int,
     *     imported: int,
     *     reused: int,
     *     skipped: int,
     *     failed: int,
     *     missing_alt_text: int
     * } $report
     */
    private function addFailure(array &$report, int $wordpressId, string $message): void
    {
        $report['failed']++;
        $report['items'][] = [
            'wordpress_id' => $wordpressId,
            'status' => 'mislukt',
            'message' => $message,
        ];
    }
}
