<?php

namespace App\Actions;

use App\Models\Article;
use App\Models\MediaAsset;
use App\Models\Redirect;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;
use JsonException;
use RuntimeException;

/**
 * @phpstan-type ImportReport array{
 *     items: list<array{wordpress_id: string, status: string, message: string}>,
 *     selected: int,
 *     ready: int,
 *     imported: int,
 *     reused: int,
 *     skipped: int,
 *     failed: int,
 *     report_path: string
 * }
 */
final class GenerateWordPressImportReport
{
    /** @var list<string> */
    private const array PHASES = ['media', 'posts', 'pages', 'cleanup', 'redirects'];

    /**
     * @return ImportReport
     *
     * @throws JsonException
     */
    public function handle(string $manifestPath, string $reportPath, bool $dryRun): array
    {
        $manifest = $this->readManifest($manifestPath);
        $report = $this->emptyReport($reportPath);
        $phaseRows = $this->collectRuns($manifest, $report);
        $diagnostics = $this->collectDiagnostics($manifest, $report);
        $mappingRows = $this->collectMappings($manifest, $report);
        $report['failed'] = count(array_filter(
            $report['items'],
            fn (array $item): bool => in_array($item['status'], ['mislukt', 'review'], true),
        ));

        if (! $dryRun) {
            $this->writeReport($reportPath, $report, $phaseRows, $diagnostics, $mappingRows);
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

    /**
     * @param  array<string, mixed>  $manifest
     * @param  ImportReport  $report
     * @return list<array<string, mixed>>
     */
    private function collectRuns(array $manifest, array &$report): array
    {
        $rows = [];

        foreach (self::PHASES as $phase) {
            $run = Arr::get($manifest, "runs.{$phase}");

            if (! is_array($run)) {
                $this->addItem($report, "phase:{$phase}", 'mislukt', "Importfase {$phase} heeft nog geen geregistreerde stagingrun.");
                $rows[] = [
                    'phase' => $phase,
                    'status' => 'not_run',
                    'selected' => 0,
                    'imported' => 0,
                    'reused' => 0,
                    'skipped' => 0,
                    'failed' => 0,
                    'completed_at' => '—',
                ];

                continue;
            }

            foreach (['selected', 'ready', 'imported', 'reused', 'skipped'] as $counter) {
                $report[$counter] += (int) Arr::get($run, $counter, 0);
            }

            $runFailed = (int) Arr::get($run, 'failed', 0);
            $rows[] = [
                'phase' => $phase,
                'status' => $runFailed > 0 ? 'failed' : 'complete',
                'selected' => (int) Arr::get($run, 'selected', 0),
                'imported' => (int) Arr::get($run, 'imported', 0),
                'reused' => (int) Arr::get($run, 'reused', 0),
                'skipped' => (int) Arr::get($run, 'skipped', 0),
                'failed' => $runFailed,
                'completed_at' => (string) Arr::get($run, 'completed_at', '—'),
            ];

            foreach ((array) Arr::get($run, 'items', []) as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $status = Arr::get($item, 'status');

                if (! in_array($status, ['mislukt', 'conflict', 'overgeslagen'], true)) {
                    continue;
                }

                $reference = (string) Arr::get($item, 'wordpress_id', 'onbekend');
                $message = (string) Arr::get($item, 'message', 'Geen details beschikbaar.');
                $this->addItem(
                    $report,
                    "{$phase}:{$reference}",
                    $status === 'overgeslagen' ? 'overgeslagen' : 'mislukt',
                    "{$phase}: {$message}",
                );
            }
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @param  ImportReport  $report
     * @return array<string, list<string>>
     */
    private function collectDiagnostics(array $manifest, array &$report): array
    {
        $diagnostics = [
            'missing_alt_text' => [],
            'unresolved_links' => [],
            'missing_media' => [],
            'suspicious_markup' => [],
            'pending_reviews' => [],
        ];

        foreach ((array) Arr::get($manifest, 'mappings.media', []) as $wordpressId => $mapping) {
            if (! is_array($mapping)) {
                continue;
            }

            $mimeType = Arr::get($mapping, 'mime_type');
            $altText = Arr::get($mapping, 'alt_text');

            if (is_string($mimeType) && Str::startsWith($mimeType, 'image/') && $altText === null) {
                $message = "Media {$wordpressId} mist herbruikbare alt-tekst.";
                $diagnostics['missing_alt_text'][] = $message;
                $this->addItem($report, "media:{$wordpressId}:alt", 'review', $message);
            }
        }

        foreach ((array) Arr::get($manifest, 'mappings.posts', []) as $wordpressId => $mapping) {
            if (! is_array($mapping)) {
                continue;
            }

            $cleanup = Arr::get($mapping, 'cleanup');

            if (! is_array($cleanup)) {
                $this->addItem($report, "post:{$wordpressId}:cleanup", 'mislukt', "Post {$wordpressId} heeft nog geen cleanupresultaat.");

                continue;
            }

            foreach (['unresolved_links', 'missing_media', 'suspicious_markup'] as $type) {
                foreach ((array) Arr::get($cleanup, $type, []) as $value) {
                    $message = "Post {$wordpressId}: ".(string) $value;
                    $diagnostics[$type][] = $message;

                    if ($type === 'suspicious_markup' && Arr::get($cleanup, 'review.status') === 'approved') {
                        continue;
                    }

                    $status = $type === 'suspicious_markup' ? 'review' : 'mislukt';
                    $this->addItem($report, "post:{$wordpressId}:{$type}:".hash('sha256', (string) $value), $status, $message);
                }
            }
        }

        foreach ((array) Arr::get($manifest, 'mappings.pages', []) as $wordpressId => $mapping) {
            $reviewStatus = is_array($mapping) ? Arr::get($mapping, 'review.status') : null;

            if ($reviewStatus === 'pending') {
                $message = "Pagina {$wordpressId} wacht op handmatige rewrite-review.";
                $diagnostics['pending_reviews'][] = $message;
                $this->addItem($report, "page:{$wordpressId}:review", 'review', $message);
            }
        }

        foreach ((array) Arr::get($manifest, 'mappings.redirects', []) as $mapping) {
            $reviewStatus = is_array($mapping) ? Arr::get($mapping, 'review.status') : null;
            $sourcePath = is_array($mapping) ? Arr::get($mapping, 'source_path') : null;

            if ($reviewStatus === 'pending' && is_string($sourcePath)) {
                $message = "Redirect {$sourcePath} wacht op review en hoort inactief te zijn.";
                $diagnostics['pending_reviews'][] = $message;
                $this->addItem($report, "redirect:{$sourcePath}:review", 'review', $message);
            }
        }

        return $diagnostics;
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @param  ImportReport  $report
     * @return array<string, list<array<string, string>>>
     */
    private function collectMappings(array $manifest, array &$report): array
    {
        $rows = ['media' => [], 'posts' => [], 'pages' => [], 'redirects' => []];

        foreach ((array) Arr::get($manifest, 'mappings.media', []) as $wordpressId => $mapping) {
            if (! is_array($mapping)) {
                continue;
            }

            $mediaAssetId = Arr::get($mapping, 'media_asset_id');
            $mediaAsset = is_int($mediaAssetId) ? MediaAsset::query()->find($mediaAssetId) : null;
            $target = $mediaAsset instanceof MediaAsset ? "MediaAsset #{$mediaAssetId}" : "Ontbrekende MediaAsset #{$mediaAssetId}";

            if (! $mediaAsset instanceof MediaAsset) {
                $this->addItem($report, "media:{$wordpressId}:target", 'mislukt', $target);
            }

            $rows['media'][] = [
                'reference' => (string) $wordpressId,
                'source' => (string) Arr::get($mapping, 'source_url', '—'),
                'target' => $target,
                'review' => Arr::get($mapping, 'alt_text') === null ? 'alt text pending' : 'complete',
            ];
        }

        foreach ((array) Arr::get($manifest, 'mappings.posts', []) as $wordpressId => $mapping) {
            if (! is_array($mapping)) {
                continue;
            }

            $articleId = Arr::get($mapping, 'article_id');
            $article = is_int($articleId) ? Article::query()->find($articleId) : null;
            $target = $article instanceof Article
                ? "Article #{$articleId} (/news/{$article->slug})"
                : "Ontbrekend Article #{$articleId}";

            if (! $article instanceof Article) {
                $this->addItem($report, "post:{$wordpressId}:target", 'mislukt', $target);
            }

            $rows['posts'][] = [
                'reference' => (string) $wordpressId,
                'source' => (string) Arr::get($mapping, 'source_url', '—'),
                'target' => $target,
                'review' => is_array(Arr::get($mapping, 'cleanup'))
                    ? (string) Arr::get($mapping, 'cleanup.review.status', 'cleaned')
                    : 'cleanup missing',
            ];
        }

        foreach ((array) Arr::get($manifest, 'mappings.pages', []) as $wordpressId => $mapping) {
            if (! is_array($mapping)) {
                continue;
            }

            $decision = (string) Arr::get($mapping, 'decision', 'unknown');
            $target = Arr::get($mapping, 'target.path');

            $rows['pages'][] = [
                'reference' => (string) $wordpressId,
                'source' => (string) Arr::get($mapping, 'source_url', '—'),
                'target' => is_string($target) ? $target : $decision,
                'review' => (string) Arr::get($mapping, 'review.status', 'unknown'),
            ];
        }

        foreach ((array) Arr::get($manifest, 'mappings.redirects', []) as $key => $mapping) {
            if (! is_array($mapping)) {
                continue;
            }

            $redirectId = Arr::get($mapping, 'redirect_id');
            $redirect = is_int($redirectId) ? Redirect::query()->find($redirectId) : null;
            $sourcePath = (string) Arr::get($mapping, 'source_path', $key);

            if (! $redirect instanceof Redirect) {
                $this->addItem($report, "redirect:{$sourcePath}:target", 'mislukt', "Ontbrekende Redirect #{$redirectId} voor {$sourcePath}.");
            }

            $rows['redirects'][] = [
                'reference' => $sourcePath,
                'source' => $sourcePath,
                'target' => (string) Arr::get($mapping, 'target_url', '—').' (Redirect #'.$redirectId.')',
                'review' => (string) Arr::get($mapping, 'review.status', 'unknown'),
            ];
        }

        return $rows;
    }

    /**
     * @param  ImportReport  $report
     * @param  list<array<string, mixed>>  $phaseRows
     * @param  array<string, list<string>>  $diagnostics
     * @param  array<string, list<array<string, string>>>  $mappingRows
     */
    private function writeReport(
        string $reportPath,
        array $report,
        array $phaseRows,
        array $diagnostics,
        array $mappingRows,
    ): void {
        File::ensureDirectoryExists(dirname($reportPath));
        $launchStatus = $report['failed'] > 0 ? 'BLOCKED' : 'READY';
        $lines = [
            '# WordPress Staging Import Review',
            '',
            "Launch review status: **{$launchStatus}**",
            '',
            $this->summaryLine($report),
            '',
            '## Phase runs',
            '',
            '| Phase | Status | Selected | Imported | Reused | Skipped | Failed | Completed |',
            '| --- | --- | ---: | ---: | ---: | ---: | ---: | --- |',
        ];

        foreach ($phaseRows as $row) {
            $lines[] = "| {$row['phase']} | {$row['status']} | {$row['selected']} | {$row['imported']} | {$row['reused']} | {$row['skipped']} | {$row['failed']} | {$row['completed_at']} |";
        }

        $this->appendItems($lines, 'Launch blockers and pending review', $report['items'], ['mislukt', 'review']);
        $this->appendItems($lines, 'Skipped records', $report['items'], ['overgeslagen']);

        $lines[] = '';
        $lines[] = '## Diagnostics';
        $lines[] = '';

        foreach ($diagnostics as $type => $values) {
            $lines[] = '### '.Str::headline($type);
            $lines[] = '';
            $lines[] = $values === [] ? '- None.' : implode(PHP_EOL, array_map(fn (string $value): string => '- '.$value, $values));
            $lines[] = '';
        }

        $lines[] = '## Temporary source-to-target mappings';

        foreach ($mappingRows as $type => $rows) {
            $lines[] = '';
            $lines[] = '### '.Str::headline($type);
            $lines[] = '';
            $lines[] = '| Reference | Source | Target | Review |';
            $lines[] = '| --- | --- | --- | --- |';

            if ($rows === []) {
                $lines[] = '| — | — | — | no mappings |';
            } else {
                foreach ($rows as $row) {
                    $lines[] = '| '.$this->markdownCell($row['reference']).' | '.$this->markdownCell($row['source']).' | '.$this->markdownCell($row['target']).' | '.$this->markdownCell($row['review']).' |';
                }
            }
        }

        $lines[] = '';
        $lines[] = '## Artifact removal policy';
        $lines[] = '';
        $lines[] = 'Remove the WordPress source snapshots, XML export, selection manifest, run history, and generated review reports only after production cutover is verified, the agreed rollback window has closed, and an archival backup is retained.';
        $lines[] = '';
        $lines[] = 'Deleting those temporary artifacts must never delete normalized Articles, MediaAssets, Locations, Events, or public Redirect records; those durable records are source-agnostic and remain operational without the importer.';

        $contents = implode(PHP_EOL, $lines).PHP_EOL;

        if (File::isFile($reportPath)) {
            File::replace($reportPath, $contents);

            return;
        }

        if (File::put($reportPath, $contents) === false) {
            throw new RuntimeException("Kon importreviewrapport niet schrijven: {$reportPath}");
        }
    }

    /**
     * @param  list<string>  $lines
     * @param  list<array<string, string>>  $items
     * @param  list<string>  $statuses
     */
    private function appendItems(array &$lines, string $heading, array $items, array $statuses): void
    {
        $lines[] = '';
        $lines[] = "## {$heading}";
        $lines[] = '';
        $matchingItems = array_filter($items, fn (array $item): bool => in_array($item['status'], $statuses, true));

        if ($matchingItems === []) {
            $lines[] = '- None.';

            return;
        }

        foreach ($matchingItems as $item) {
            $lines[] = "- {$item['wordpress_id']} [{$item['status']}]: {$item['message']}";
        }
    }

    /** @param ImportReport $report */
    private function summaryLine(array $report): string
    {
        return sprintf(
            'Geselecteerd: %d | Klaar: %d | Geïmporteerd: %d | Hergebruikt: %d | Overgeslagen: %d | Mislukt: %d',
            $report['selected'],
            $report['ready'],
            $report['imported'],
            $report['reused'],
            $report['skipped'],
            $report['failed'],
        );
    }

    private function markdownCell(string $value): string
    {
        return str_replace('|', '\\|', $value);
    }

    /** @param ImportReport $report */
    private function addItem(array &$report, string $reference, string $status, string $message): void
    {
        foreach ($report['items'] as $item) {
            if ($item['wordpress_id'] === $reference && $item['status'] === $status && $item['message'] === $message) {
                return;
            }
        }

        $report['items'][] = [
            'wordpress_id' => $reference,
            'status' => $status,
            'message' => $message,
        ];
    }

    /** @return ImportReport */
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
}
