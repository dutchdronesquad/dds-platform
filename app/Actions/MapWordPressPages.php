<?php

namespace App\Actions;

use App\Models\Location;
use App\Support\WordPressSourceRepository;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use InvalidArgumentException;
use JsonException;
use RuntimeException;

final class MapWordPressPages
{
    public function __construct(private readonly WordPressSourceRepository $sourceRepository) {}

    /** @var list<string> */
    private const array PUBLIC_ROUTE_NAMES = [
        'home',
        'events.index',
        'news.index',
        'about',
        'house_rules',
        'contact',
        'partners',
        'locations.index',
        'getting_started.index',
    ];

    /** @var list<string> */
    private const array MANUAL_TARGET_KEYS = [
        'media-overview',
        'partner-catalogue',
    ];

    /**
     * @return array{
     *     items: list<array{wordpress_id: int, status: string, message: string}>,
     *     selected: int,
     *     ready: int,
     *     imported: int,
     *     reused: int,
     *     skipped: int,
     *     failed: int,
     *     report_path: string
     * }
     *
     * @throws JsonException
     */
    public function handle(string $manifestPath, string $reportPath, bool $dryRun): array
    {
        $manifest = $this->readManifest($manifestPath);
        $pagesEndpoint = $this->pagesEndpoint($manifest);
        $selections = $this->selections($manifest);
        $sourcePages = $this->sourceRepository->records($manifest, 'pages', $pagesEndpoint);
        $report = $this->emptyReport($reportPath);
        $sourcePagesById = [];
        $selectionsById = [];
        $reportRows = [];

        foreach ($sourcePages as $sourcePage) {
            $wordpressId = Arr::get($sourcePage, 'id');

            if (! is_int($wordpressId) || $wordpressId < 1 || isset($sourcePagesById[$wordpressId])) {
                throw new RuntimeException('WordPress REST gaf een ongeldige of dubbele pagina-ID terug.');
            }

            $sourcePagesById[$wordpressId] = $sourcePage;
        }

        foreach ($selections as $selection) {
            $selectionsById[$selection['wordpress_id']] = $selection;
        }

        foreach ($sourcePagesById as $wordpressId => $sourcePage) {
            if (! isset($selectionsById[$wordpressId])) {
                $title = $this->sourceTitle($sourcePage);
                $this->addFailure(
                    $report,
                    $wordpressId,
                    "Geen manifestbesluit voor gepubliceerde WordPress-pagina {$title}.",
                );
                $reportRows[] = [
                    'wordpress_id' => $wordpressId,
                    'source' => $title,
                    'decision' => 'missing',
                    'destination' => 'Geen',
                    'review' => 'blokkade',
                ];
            }
        }

        foreach ($selections as $selection) {
            $wordpressId = $selection['wordpress_id'];
            $sourcePage = $sourcePagesById[$wordpressId] ?? null;

            if (! is_array($sourcePage)) {
                $this->addFailure(
                    $report,
                    $wordpressId,
                    'Manifestpagina ontbreekt in de gepubliceerde WordPress REST-inventaris.',
                );

                continue;
            }

            $source = $this->sourceData($sourcePage, $selection);

            if (is_string($source)) {
                $this->addFailure($report, $wordpressId, $source);

                continue;
            }

            $target = $this->resolveTarget($selection);

            if (is_string($target)) {
                $this->addFailure($report, $wordpressId, $target);

                continue;
            }

            $report['selected']++;
            $reviewRequired = $selection['decision'] === 'rewrite';
            $existingMapping = Arr::get($manifest, "mappings.pages.{$wordpressId}");
            $existingChecksum = is_array($existingMapping)
                ? Arr::get($existingMapping, 'content_checksum_sha256')
                : null;
            $contentChecksum = hash('sha256', $source['content']);
            $review = $this->reviewState(
                existingMapping: $existingMapping,
                reviewRequired: $reviewRequired,
                sourceChanged: is_string($existingChecksum) && $existingChecksum !== $contentChecksum,
            );

            if ($selection['decision'] === 'skip' || $selection['decision'] === 'gone') {
                $report['skipped']++;
            } elseif ($dryRun) {
                $report['ready']++;
            } elseif (is_array($existingMapping) && $existingChecksum === $contentChecksum) {
                $report['reused']++;
            } else {
                $report['imported']++;
            }

            $status = match (true) {
                $selection['decision'] === 'skip' => 'overgeslagen',
                $selection['decision'] === 'gone' => '410',
                $dryRun => 'klaar',
                is_array($existingMapping) && $existingChecksum === $contentChecksum => 'hergebruikt',
                default => 'gekoppeld',
            };

            $destination = $this->destinationLabel($target);
            $report['items'][] = [
                'wordpress_id' => $wordpressId,
                'status' => $status,
                'message' => "{$source['title']} → {$destination}",
            ];
            $reportRows[] = [
                'wordpress_id' => $wordpressId,
                'source' => $source['title'],
                'decision' => $selection['decision'],
                'destination' => $destination,
                'review' => $review['status'],
            ];

            if ($dryRun) {
                continue;
            }

            Arr::set($manifest, "mappings.pages.{$wordpressId}", [
                'source_url' => $source['source_url'],
                'source_slug' => $source['slug'],
                'source_title' => $source['title'],
                'featured_media_id' => $source['featured_media_id'],
                'decision' => $selection['decision'],
                'target' => $target,
                'reason' => $selection['reason'],
                'content_checksum_sha256' => $contentChecksum,
                'content_excerpt' => $source['content_excerpt'],
                'review' => $review,
                'mapped_at' => now()->toIso8601String(),
            ]);
        }

        if (! $dryRun) {
            $this->writeManifest($manifestPath, $manifest);
            $this->writeReport($reportPath, $report, $reportRows);
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
    private function pagesEndpoint(array $manifest): string
    {
        $pagesEndpoint = Arr::get($manifest, 'source.pages_endpoint');

        if (! $this->isHttpUrl($pagesEndpoint)) {
            throw new InvalidArgumentException(
                'Het manifest mist een geldige HTTP(S)-URL in source.pages_endpoint.',
            );
        }

        return rtrim($pagesEndpoint, '/');
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @return list<array{
     *     wordpress_id: int,
     *     slug: string,
     *     title: string,
     *     decision: 'rewrite'|'redirect'|'gone'|'skip',
     *     target: array<string, mixed>|null,
     *     reason: string|null
     * }>
     */
    private function selections(array $manifest): array
    {
        $pages = Arr::get($manifest, 'pages');

        if (! is_array($pages) || ! array_is_list($pages)) {
            throw new InvalidArgumentException('Het manifest moet een pages-lijst bevatten.');
        }

        $selections = [];
        $wordpressIds = [];

        foreach ($pages as $index => $selection) {
            if (! is_array($selection)) {
                throw new InvalidArgumentException("Paginaselectie {$index} moet een JSON-object zijn.");
            }

            $wordpressId = Arr::get($selection, 'wordpress_id');
            $slug = Arr::get($selection, 'slug');
            $title = Arr::get($selection, 'title');
            $decision = Arr::get($selection, 'decision');
            $target = Arr::get($selection, 'target');
            $reason = Arr::get($selection, 'reason');

            if (! is_int($wordpressId) || $wordpressId < 1) {
                throw new InvalidArgumentException(
                    "Paginaselectie {$index} mist een geldige wordpress_id.",
                );
            }

            if (isset($wordpressIds[$wordpressId])) {
                throw new InvalidArgumentException("WordPress pagina-ID {$wordpressId} staat dubbel in het manifest.");
            }

            if (! is_string($slug) || $slug === '') {
                throw new InvalidArgumentException("Paginaselectie {$wordpressId} heeft een ongeldige slug.");
            }

            if (! is_string($title) || trim($title) === '') {
                throw new InvalidArgumentException("Paginaselectie {$wordpressId} heeft een ongeldige title.");
            }

            if (! in_array($decision, ['rewrite', 'redirect', 'gone', 'skip'], true)) {
                throw new InvalidArgumentException(
                    "Paginaselectie {$wordpressId} heeft een ongeldige decision.",
                );
            }

            if (in_array($decision, ['rewrite', 'redirect'], true) && ! is_array($target)) {
                throw new InvalidArgumentException(
                    "Paginaselectie {$wordpressId} mist een expliciet target.",
                );
            }

            if (in_array($decision, ['gone', 'skip'], true) && (! is_string($reason) || trim($reason) === '')) {
                throw new InvalidArgumentException(
                    "Paginaselectie {$wordpressId} mist een reason.",
                );
            }

            $wordpressIds[$wordpressId] = true;
            $selections[] = [
                'wordpress_id' => $wordpressId,
                'slug' => $slug,
                'title' => $this->plainText($title),
                'decision' => $decision,
                'target' => is_array($target) ? $target : null,
                'reason' => is_string($reason) ? $reason : null,
            ];
        }

        return $selections;
    }

    /**
     * @param  array<string, mixed>  $sourcePage
     * @param array{
     *     wordpress_id: int,
     *     slug: string,
     *     title: string,
     *     decision: 'rewrite'|'redirect'|'gone'|'skip',
     *     target: array<string, mixed>|null,
     *     reason: string|null
     * } $selection
     * @return array{
     *     source_url: string,
     *     slug: string,
     *     title: string,
     *     featured_media_id: int,
     *     content: string,
     *     content_excerpt: string
     * }|string
     */
    private function sourceData(array $sourcePage, array $selection): array|string
    {
        if (Arr::get($sourcePage, 'status') !== 'publish') {
            return 'WordPress REST-pagina is niet gepubliceerd.';
        }

        $sourceUrl = Arr::get($sourcePage, 'link');
        $slug = Arr::get($sourcePage, 'slug');
        $title = $this->sourceTitle($sourcePage);
        $featuredMediaId = Arr::get($sourcePage, 'featured_media', 0);
        $content = Arr::get($sourcePage, 'content.rendered', '');

        if (! $this->isHttpUrl($sourceUrl)) {
            return 'WordPress REST gaf geen geldige paginabron-URL terug.';
        }

        if (! is_string($slug) || $slug !== $selection['slug']) {
            return "Bronslug komt niet overeen met manifest: {$selection['slug']}.";
        }

        if (! is_int($featuredMediaId) || $featuredMediaId < 0) {
            return 'WordPress REST gaf geen geldige featured-media-ID terug.';
        }

        if (! is_string($content)) {
            return 'WordPress REST gaf geen geldige pagina-inhoud terug.';
        }

        return [
            'source_url' => $sourceUrl,
            'slug' => $slug,
            'title' => $title,
            'featured_media_id' => $featuredMediaId,
            'content' => $content,
            'content_excerpt' => Str::limit($this->plainText($content), 300),
        ];
    }

    /**
     * @param array{
     *     wordpress_id: int,
     *     slug: string,
     *     title: string,
     *     decision: 'rewrite'|'redirect'|'gone'|'skip',
     *     target: array<string, mixed>|null,
     *     reason: string|null
     * } $selection
     * @return array<string, mixed>|string
     */
    private function resolveTarget(array $selection): array|string
    {
        if ($selection['decision'] === 'gone') {
            return ['type' => 'gone', 'status_code' => 410];
        }

        if ($selection['decision'] === 'skip') {
            return ['type' => 'skip'];
        }

        $target = $selection['target'];
        $type = is_array($target) ? Arr::get($target, 'type') : null;

        if ($type === 'location') {
            $locationSlug = Arr::get($target, 'location_slug');
            $location = is_string($locationSlug)
                ? Location::query()->where('slug', $locationSlug)->first()
                : null;

            if (! $location instanceof Location) {
                return "Doellocatie {$locationSlug} bestaat niet.";
            }

            return [
                'type' => 'location',
                'location_id' => $location->getKey(),
                'location_slug' => $location->slug,
                'path' => route('locations.show', ['location' => $location], false),
            ];
        }

        if ($type === 'route') {
            $routeName = Arr::get($target, 'route_name');
            $query = Arr::get($target, 'query', []);

            if (! is_string($routeName)
                || ! in_array($routeName, self::PUBLIC_ROUTE_NAMES, true)
                || ! Route::has($routeName)) {
                return "Doelroute {$routeName} is geen ondersteunde publieke route.";
            }

            if (! is_array($query) || ! $this->isScalarMap($query)) {
                return "Doelroute {$routeName} heeft ongeldige queryparameters.";
            }

            return [
                'type' => 'route',
                'route_name' => $routeName,
                'path' => route($routeName, $query, false),
            ];
        }

        if ($type === 'manual') {
            $key = Arr::get($target, 'key');
            $path = Arr::get($target, 'path');

            if (! is_string($key)
                || ! in_array($key, self::MANUAL_TARGET_KEYS, true)
                || ! is_string($path)
                || ! str_starts_with($path, '/')) {
                return 'Handmatig doel is niet ondersteund of mist een absoluut pad.';
            }

            return [
                'type' => 'manual',
                'key' => $key,
                'path' => $path,
            ];
        }

        return 'Paginadoel moet een location, route of goedgekeurd manual target zijn.';
    }

    /**
     * @return array{status: string, notes: string|null, source_changed: bool}
     */
    private function reviewState(
        mixed $existingMapping,
        bool $reviewRequired,
        bool $sourceChanged,
    ): array {
        $existingReview = is_array($existingMapping)
            ? Arr::get($existingMapping, 'review')
            : null;
        $existingStatus = is_array($existingReview)
            ? Arr::get($existingReview, 'status')
            : null;
        $existingNotes = is_array($existingReview)
            ? Arr::get($existingReview, 'notes')
            : null;

        return [
            'status' => $reviewRequired
                ? ($sourceChanged ? 'pending' : (is_string($existingStatus) ? $existingStatus : 'pending'))
                : 'not_required',
            'notes' => is_string($existingNotes) ? $existingNotes : null,
            'source_changed' => $sourceChanged,
        ];
    }

    /** @param array<string, mixed> $target */
    private function destinationLabel(array $target): string
    {
        return match (Arr::get($target, 'type')) {
            'location' => 'Location '.Arr::get($target, 'location_slug'),
            'route' => (string) Arr::get($target, 'path'),
            'manual' => 'Handmatig '.Arr::get($target, 'key').' ('.Arr::get($target, 'path').')',
            'gone' => '410 Gone',
            default => 'Expliciet overgeslagen',
        };
    }

    /** @param array<string, mixed> $sourcePage */
    private function sourceTitle(array $sourcePage): string
    {
        $title = Arr::get($sourcePage, 'title.rendered');

        return is_string($title) && trim($title) !== ''
            ? $this->plainText($title)
            : 'Zonder titel';
    }

    private function plainText(string $value): string
    {
        return Str::of(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'))
            ->stripTags()
            ->squish()
            ->toString();
    }

    private function isHttpUrl(mixed $value): bool
    {
        return is_string($value)
            && filter_var($value, FILTER_VALIDATE_URL) !== false
            && in_array(parse_url($value, PHP_URL_SCHEME), ['http', 'https'], true);
    }

    /** @param array<mixed> $value */
    private function isScalarMap(array $value): bool
    {
        foreach ($value as $key => $item) {
            if (! is_string($key) || ! is_scalar($item)) {
                return false;
            }
        }

        return true;
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
     * @param array{
     *     items: list<array{wordpress_id: int, status: string, message: string}>,
     *     selected: int,
     *     ready: int,
     *     imported: int,
     *     reused: int,
     *     skipped: int,
     *     failed: int,
     *     report_path: string
     * } $report
     * @param list<array{
     *     wordpress_id: int,
     *     source: string,
     *     decision: string,
     *     destination: string,
     *     review: string
     * }> $rows
     */
    private function writeReport(string $reportPath, array $report, array $rows): void
    {
        File::ensureDirectoryExists(dirname($reportPath));

        $lines = [
            '# WordPress Page Mapping Review',
            '',
            "Selected: {$report['selected']}; mapped: {$report['imported']}; reused: {$report['reused']}; skipped: {$report['skipped']}; failed: {$report['failed']}.",
            '',
            '| WordPress ID | Source | Decision | Destination | Review |',
            '| ---: | --- | --- | --- | --- |',
        ];

        foreach ($rows as $row) {
            $lines[] = sprintf(
                '| %d | %s | %s | %s | %s |',
                $row['wordpress_id'],
                str_replace('|', '\\|', $row['source']),
                $row['decision'],
                str_replace('|', '\\|', $row['destination']),
                $row['review'],
            );
        }

        $lines[] = '';
        $lines[] = '## Manual rewrite work';
        $lines[] = '';

        foreach ($rows as $row) {
            if ($row['review'] === 'not_required') {
                continue;
            }

            $lines[] = "- WordPress {$row['wordpress_id']} ({$row['source']}) → {$row['destination']}: {$row['review']}.";
        }

        if ($report['failed'] > 0) {
            $lines[] = '';
            $lines[] = '## Blocking failures';
            $lines[] = '';

            foreach ($report['items'] as $item) {
                if ($item['status'] === 'mislukt') {
                    $lines[] = "- WordPress {$item['wordpress_id']}: {$item['message']}";
                }
            }
        }

        $contents = implode(PHP_EOL, $lines).PHP_EOL;

        if (File::isFile($reportPath)) {
            File::replace($reportPath, $contents);

            return;
        }

        if (File::put($reportPath, $contents) === false) {
            throw new RuntimeException("Kon reviewrapport niet schrijven: {$reportPath}");
        }
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
     *     report_path: string
     * }
     */
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
            'report_path' => $reportPath,
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
     *     report_path: string
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
