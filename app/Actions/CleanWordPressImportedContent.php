<?php

namespace App\Actions;

use App\Models\Article;
use App\Models\MediaAsset;
use App\Support\WordPressContentNormalizer;
use App\Support\WordPressSourceRepository;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use JsonException;

/**
 * @phpstan-type CleanupReport array{
 *     items: list<array{wordpress_id: int, status: string, message: string}>,
 *     selected: int,
 *     ready: int,
 *     imported: int,
 *     reused: int,
 *     skipped: int,
 *     failed: int,
 *     unresolved_links: int,
 *     missing_media: int,
 *     suspicious_markup: int,
 *     report_path: string
 * }
 */
final class CleanWordPressImportedContent
{
    public function __construct(
        private readonly WordPressContentNormalizer $normalizer,
        private readonly WordPressSourceRepository $sourceRepository,
    ) {}

    /**
     * @return CleanupReport
     *
     * @throws JsonException
     */
    public function handle(
        string $manifestPath,
        string $reportPath,
        bool $dryRun,
        bool $refreshSource = false,
    ): array {
        $manifest = $this->readManifest($manifestPath);
        $postMappings = Arr::get($manifest, 'mappings.posts');

        if (! is_array($postMappings)) {
            throw new InvalidArgumentException('Het manifest bevat geen mappings.posts; voer eerst de postimport uit.');
        }

        $report = $this->emptyReport($reportPath);
        $normalizerVersion = (int) config('wordpress-import.cleanup.normalizer_version', 1);
        $linkMappings = $this->linkMappings($manifest);
        [$mediaMappings, $importedImagesByUrl] = $this->mediaData($manifest);
        $internalHosts = $this->internalHosts($manifest);
        $reportRows = [];

        foreach ($postMappings as $wordpressIdValue => $mapping) {
            $wordpressId = filter_var($wordpressIdValue, FILTER_VALIDATE_INT);

            if ($wordpressId === false || $wordpressId < 1 || ! is_array($mapping)) {
                $this->addFailure($report, (int) $wordpressIdValue, 'Ongeldige postmapping in het manifest.');

                continue;
            }

            $report['selected']++;
            $article = $this->article($mapping);
            $sourceUrl = Arr::get($mapping, 'source_url');
            $sourceChecksum = Arr::get($mapping, 'content_checksum_sha256');

            if (! $article instanceof Article || ! is_string($sourceUrl) || ! filter_var($sourceUrl, FILTER_VALIDATE_URL) || ! is_string($sourceChecksum)) {
                $this->addFailure($report, $wordpressId, 'Postmapping mist een bestaand Article, een bron-URL of de bronchecksum.');

                continue;
            }

            $currentChecksum = hash('sha256', $article->content);
            $existingCleanup = Arr::get($mapping, 'cleanup');
            $previousOutputChecksum = is_array($existingCleanup)
                ? Arr::get($existingCleanup, 'output_checksum_sha256')
                : null;

            $hasUnchangedCleanupOutput = is_string($previousOutputChecksum)
                && hash_equals($previousOutputChecksum, $currentChecksum);
            $requiresCleanupUpgrade = $hasUnchangedCleanupOutput
                && (
                    Arr::get($existingCleanup, 'format') !== 'markdown'
                    || Arr::get($existingCleanup, 'normalizer_version') !== $normalizerVersion
                );

            if ($hasUnchangedCleanupOutput && ! $refreshSource && ! $requiresCleanupUpgrade) {
                $cleanup = $existingCleanup;

                $report['reused']++;
                $report['items'][] = [
                    'wordpress_id' => $wordpressId,
                    'status' => 'hergebruikt',
                    'message' => "Article {$article->getKey()} was al opgeschoond.",
                ];
                $this->appendDiagnostics($report, $cleanup);
                $reportRows[] = $this->reportRow($wordpressId, $article, $cleanup);

                continue;
            }

            if (! hash_equals($sourceChecksum, $currentChecksum) && ! $hasUnchangedCleanupOutput) {
                $this->addFailure($report, $wordpressId, "Article {$article->getKey()} wijkt af van de geïmporteerde bron; handmatige inhoud is niet overschreven.");

                continue;
            }

            $sourceContent = $refreshSource || $requiresCleanupUpgrade
                ? $this->sourceContent($manifest, $wordpressId, $sourceChecksum)
                : $article->content;

            if (! is_string($sourceContent)) {
                $this->addFailure($report, $wordpressId, $sourceContent['error']);

                continue;
            }

            $result = $this->normalizer->normalize(
                html: $sourceContent,
                sourceUrl: $sourceUrl,
                linkMappings: $linkMappings,
                mediaMappings: $mediaMappings,
                internalHosts: $internalHosts,
            );
            $fallbackCover = $article->cover_image_id === null
                ? $this->firstImportedImage($importedImagesByUrl, $result['embedded_media_urls'])
                : null;
            $cleanup = [
                'format' => 'markdown',
                'normalizer_version' => $normalizerVersion,
                'source_checksum_sha256' => $sourceChecksum,
                'output_checksum_sha256' => hash('sha256', $result['content']),
                'unresolved_links' => $result['unresolved_links'],
                'missing_media' => $result['missing_media'],
                'suspicious_markup' => $result['suspicious_markup'],
                'transformations' => $result['transformations'],
                'cleaned_at' => now()->toIso8601String(),
            ];

            if ($fallbackCover instanceof MediaAsset) {
                $cleanup['fallback_cover_media_asset_id'] = $fallbackCover->getKey();
            }

            if (
                Arr::get($existingCleanup, 'output_checksum_sha256') === $cleanup['output_checksum_sha256']
                && is_array(Arr::get($existingCleanup, 'review'))
            ) {
                $cleanup['review'] = Arr::get($existingCleanup, 'review');
            }
            $this->appendDiagnostics($report, $cleanup);
            $reportRows[] = $this->reportRow($wordpressId, $article, $cleanup);

            if ($dryRun) {
                $report['ready']++;
                $report['items'][] = [
                    'wordpress_id' => $wordpressId,
                    'status' => 'klaar',
                    'message' => "Article {$article->getKey()} kan naar veilige Markdown worden opgeschoond.",
                ];

                continue;
            }

            DB::transaction(function () use ($article, $fallbackCover, $result): void {
                $attributes = ['content' => $result['content']];

                if ($fallbackCover instanceof MediaAsset) {
                    $attributes['cover_image_id'] = $fallbackCover->getKey();
                }

                $article->update($attributes);
            });

            Arr::set($manifest, "mappings.posts.{$wordpressId}.cleanup", $cleanup);
            $report['imported']++;
            $report['items'][] = [
                'wordpress_id' => $wordpressId,
                'status' => 'opgeschoond',
                'message' => "Article {$article->getKey()} is als veilige Markdown opgeslagen.",
            ];
        }

        if (! $dryRun) {
            $this->writeManifest($manifestPath, $manifest);
            $this->writeReport($reportPath, $report, $reportRows);
        }

        return $report;
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

    /** @param array<string, mixed> $mapping */
    private function article(array $mapping): ?Article
    {
        $articleId = Arr::get($mapping, 'article_id');

        return is_int($articleId) ? Article::query()->find($articleId) : null;
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @return string|array{error: string}
     */
    private function sourceContent(array $manifest, int $wordpressId, string $expectedChecksum): string|array
    {
        $postsEndpoint = Arr::get($manifest, 'source.posts_endpoint');

        if (! is_string($postsEndpoint) || filter_var($postsEndpoint, FILTER_VALIDATE_URL) === false) {
            return ['error' => 'Het manifest mist een geldige WordPress posts-endpoint.'];
        }

        $record = $this->sourceRepository->record(
            $manifest,
            'posts',
            rtrim($postsEndpoint, '/'),
            $wordpressId,
        );

        if (is_string($record)) {
            return ['error' => $record];
        }

        $content = Arr::get($record, 'content.rendered');

        if (! is_string($content) || ! hash_equals($expectedChecksum, hash('sha256', $content))) {
            return ['error' => "WordPress broninhoud {$wordpressId} wijkt af van de vastgelegde checksum."];
        }

        return $content;
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @return array<string, string>
     */
    private function linkMappings(array $manifest): array
    {
        $mappings = [];

        foreach ((array) Arr::get($manifest, 'mappings.posts', []) as $mapping) {
            if (! is_array($mapping)) {
                continue;
            }

            $sourceUrl = Arr::get($mapping, 'source_url');
            $articleId = Arr::get($mapping, 'article_id');
            $article = is_int($articleId) ? Article::query()->find($articleId) : null;

            if (is_string($sourceUrl) && $article instanceof Article) {
                $mappings[$this->canonicalUrl($sourceUrl)] = '/news/'.$article->slug;
            }
        }

        foreach ((array) Arr::get($manifest, 'mappings.pages', []) as $mapping) {
            $sourceUrl = is_array($mapping) ? Arr::get($mapping, 'source_url') : null;
            $targetType = is_array($mapping) ? Arr::get($mapping, 'target.type') : null;
            $path = is_array($mapping) ? Arr::get($mapping, 'target.path') : null;

            if (is_string($sourceUrl) && is_string($path) && in_array($targetType, ['route', 'location'], true)) {
                $mappings[$this->canonicalUrl($sourceUrl)] = $path;
            }
        }

        foreach ((array) config('wordpress-import.cleanup.unavailable_links', []) as $sourceUrl => $replacement) {
            if (is_string($sourceUrl) && is_string($replacement) && $replacement !== '') {
                $mappings[$this->canonicalUrl($sourceUrl)] = $replacement;
            }
        }

        return $mappings;
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @return array{array<string, string>, array<string, MediaAsset>}
     */
    private function mediaData(array $manifest): array
    {
        $mappingRows = [];
        $mediaAssetIds = [];

        foreach ((array) Arr::get($manifest, 'mappings.media', []) as $mapping) {
            if (! is_array($mapping)) {
                continue;
            }

            $sourceUrl = Arr::get($mapping, 'source_url');
            $mediaAssetId = Arr::get($mapping, 'media_asset_id');

            if (is_string($sourceUrl) && is_int($mediaAssetId)) {
                $mappingRows[] = [
                    'source_url' => $sourceUrl,
                    'media_asset_id' => $mediaAssetId,
                ];
                $mediaAssetIds[] = $mediaAssetId;
            }
        }

        $mediaAssets = MediaAsset::query()
            ->with('media')
            ->whereIn('id', array_values(array_unique($mediaAssetIds)))
            ->get()
            ->keyBy('id');
        $mediaMappings = [];
        $importedImagesByUrl = [];

        foreach ($mappingRows as $mapping) {
            $mediaAsset = $mediaAssets->get($mapping['media_asset_id']);

            if (! $mediaAsset instanceof MediaAsset || $mediaAsset->url() === '') {
                continue;
            }

            $mediaMappings[$this->canonicalMediaUrl($mapping['source_url'])] = $mediaAsset->url();

            if ($mediaAsset->isImage()) {
                $importedImagesByUrl[$mediaAsset->url()] = $mediaAsset;
            }
        }

        return [$mediaMappings, $importedImagesByUrl];
    }

    /**
     * @param  array<string, MediaAsset>  $importedImagesByUrl
     * @param  list<string>  $embeddedMediaUrls
     */
    private function firstImportedImage(array $importedImagesByUrl, array $embeddedMediaUrls): ?MediaAsset
    {
        foreach ($embeddedMediaUrls as $embeddedMediaUrl) {
            $mediaAsset = $importedImagesByUrl[$embeddedMediaUrl] ?? null;

            if ($mediaAsset instanceof MediaAsset) {
                return $mediaAsset;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @return list<string>
     */
    private function internalHosts(array $manifest): array
    {
        $hosts = [];
        $urls = [
            Arr::get($manifest, 'source.posts_endpoint'),
            Arr::get($manifest, 'source.pages_endpoint'),
            ...$this->sourceUrls($manifest),
        ];

        foreach ($urls as $url) {
            $host = is_string($url) ? strtolower((string) parse_url($url, PHP_URL_HOST)) : '';

            if ($host !== '' && ! in_array($host, $hosts, true)) {
                $hosts[] = $host;
            }
        }

        return $hosts;
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @return list<string>
     */
    private function sourceUrls(array $manifest): array
    {
        $urls = [];

        foreach (['posts', 'pages', 'media'] as $type) {
            foreach ((array) Arr::get($manifest, "mappings.{$type}", []) as $mapping) {
                $sourceUrl = is_array($mapping) ? Arr::get($mapping, 'source_url') : null;

                if (is_string($sourceUrl)) {
                    $urls[] = $sourceUrl;
                }
            }
        }

        return $urls;
    }

    /**
     * @param  CleanupReport  $report
     * @param  array<string, mixed>  $cleanup
     */
    private function appendDiagnostics(array &$report, array $cleanup): void
    {
        $report['unresolved_links'] += count((array) Arr::get($cleanup, 'unresolved_links', []));
        $report['missing_media'] += count((array) Arr::get($cleanup, 'missing_media', []));
        $report['suspicious_markup'] += count((array) Arr::get($cleanup, 'suspicious_markup', []));
    }

    /**
     * @param  array<string, mixed>  $cleanup
     * @return array<string, mixed>
     */
    private function reportRow(int $wordpressId, Article $article, array $cleanup): array
    {
        return [
            'wordpress_id' => $wordpressId,
            'article_id' => $article->getKey(),
            'title' => $article->title,
            'cleanup' => $cleanup,
        ];
    }

    /** @param array<string, mixed> $manifest */
    private function writeManifest(string $manifestPath, array $manifest): void
    {
        File::ensureDirectoryExists(dirname($manifestPath));
        $temporaryPath = $manifestPath.'.tmp';
        File::put($temporaryPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL);
        File::move($temporaryPath, $manifestPath);
    }

    /**
     * @param  array<string, mixed>  $report
     * @param  list<array<string, mixed>>  $rows
     */
    private function writeReport(string $reportPath, array $report, array $rows): void
    {
        $lines = [
            '# WordPress Content Cleanup Review',
            '',
            "- Articles: {$report['selected']}",
            "- Unresolved internal links: {$report['unresolved_links']}",
            "- Missing imported media: {$report['missing_media']}",
            "- Suspicious markup: {$report['suspicious_markup']}",
            '',
        ];

        foreach ($rows as $row) {
            $cleanup = $row['cleanup'];
            $lines[] = "## {$row['wordpress_id']} — {$row['title']}";
            $lines[] = '';
            $lines[] = "Article: {$row['article_id']} · format: `plain_text`";
            $lines[] = '';

            foreach ([
                'unresolved_links' => 'Unresolved internal links',
                'missing_media' => 'Missing imported media',
                'suspicious_markup' => 'Suspicious markup',
                'transformations' => 'Transformations',
            ] as $key => $heading) {
                $lines[] = "### {$heading}";
                $lines[] = '';
                $values = (array) Arr::get($cleanup, $key, []);
                $lines[] = $values === [] ? '- None' : implode("\n", array_map(fn (mixed $value): string => '- '.(string) $value, $values));
                $lines[] = '';
            }
        }

        File::ensureDirectoryExists(dirname($reportPath));
        File::put($reportPath, implode("\n", $lines).PHP_EOL);
    }

    /** @param CleanupReport $report */
    private function addFailure(array &$report, int $wordpressId, string $message): void
    {
        $report['failed']++;
        $report['items'][] = [
            'wordpress_id' => $wordpressId,
            'status' => 'mislukt',
            'message' => $message,
        ];
    }

    /** @return CleanupReport */
    private function emptyReport(string $reportPath): array
    {
        return [
            'items' => [],
            'selected' => 0,
            'ready' => 0,
            'imported' => 0,
            'reused' => 0,
            'skipped' => 0,
            'failed' => 0,
            'unresolved_links' => 0,
            'missing_media' => 0,
            'suspicious_markup' => 0,
            'report_path' => $reportPath,
        ];
    }

    private function canonicalUrl(string $url): string
    {
        $parts = parse_url($url);

        if (! is_array($parts) || ! isset($parts['host'])) {
            return rtrim($url, '/');
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? 'https'));
        $host = strtolower($parts['host']);
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $path = rtrim($parts['path'] ?? '', '/');

        return "{$scheme}://{$host}{$port}{$path}";
    }

    private function canonicalMediaUrl(string $url): string
    {
        $url = preg_replace('/-\d+x\d+(?=\.[a-z0-9]+(?:\?|$))/i', '', $url) ?? $url;

        return $this->canonicalUrl($url);
    }
}
