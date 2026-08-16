<?php

namespace App\Actions;

use App\Models\Article;
use App\Models\Redirect;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;
use JsonException;
use RuntimeException;

/**
 * @phpstan-type RedirectReport array{
 *     items: list<array{wordpress_id: string, status: string, message: string}>,
 *     selected: int,
 *     ready: int,
 *     imported: int,
 *     reused: int,
 *     skipped: int,
 *     failed: int,
 *     conflicts: int,
 *     pending_review: int,
 *     report_path: string
 * }
 */
final class ImportWordPressRedirects
{
    /**
     * @return RedirectReport
     *
     * @throws JsonException
     */
    public function handle(string $manifestPath, string $reportPath, bool $dryRun): array
    {
        $manifest = $this->readManifest($manifestPath);
        $report = $this->emptyReport($reportPath);
        $candidates = [
            ...$this->postCandidates($manifest),
            ...$this->pageCandidates($manifest, $report),
            ...$this->explicitCandidates($manifest, $report),
        ];
        $rows = [];

        foreach ($this->groupCandidates($candidates) as $sourcePath => $sourceCandidates) {
            $report['selected']++;
            $targets = array_values(array_unique(array_map(
                fn (array $candidate): string => $candidate['target_url'].'|'.$candidate['status_code'],
                $sourceCandidates,
            )));

            if (count($targets) > 1) {
                $targetLabels = implode(', ', array_map(
                    fn (array $candidate): string => $candidate['target_url'].' ('.$candidate['provenance'].')',
                    $sourceCandidates,
                ));
                $this->addConflict(
                    $report,
                    $sourcePath,
                    "Meerdere doelen voor hetzelfde bronpad: {$targetLabels}.",
                );
                $rows[] = $this->conflictRow($sourcePath, $targetLabels);

                continue;
            }

            $candidate = $this->mergeCandidates($sourceCandidates);

            if ($this->isSelfRedirect($candidate['source_path'], $candidate['target_url'])) {
                $this->addConflict($report, $sourcePath, 'Bron en doel verwijzen naar hetzelfde pad.');
                $rows[] = $this->conflictRow($sourcePath, $candidate['target_url']);

                continue;
            }

            $existingRedirect = Redirect::query()
                ->where('source_path', $sourcePath)
                ->first();

            if ($existingRedirect instanceof Redirect
                && ($existingRedirect->target_url !== $candidate['target_url']
                    || $existingRedirect->status_code !== $candidate['status_code'])) {
                $this->addConflict(
                    $report,
                    $sourcePath,
                    "Bestaande Redirect {$existingRedirect->getKey()} wijst naar {$existingRedirect->target_url}; voorgesteld is {$candidate['target_url']}.",
                );
                $rows[] = $this->conflictRow($sourcePath, $candidate['target_url']);

                continue;
            }

            if ($candidate['review_status'] === 'pending') {
                $report['pending_review']++;
            }

            if ($dryRun && ! $existingRedirect instanceof Redirect) {
                $report['ready']++;
                $report['items'][] = [
                    'wordpress_id' => $candidate['reference'],
                    'status' => 'klaar',
                    'message' => "{$sourcePath} → {$candidate['target_url']}",
                ];
                $rows[] = $this->candidateRow($candidate, null, 'ready');

                continue;
            }

            if ($existingRedirect instanceof Redirect) {
                if (! $dryRun && $this->shouldActivateApprovedRedirect($manifest, $candidate, $existingRedirect)) {
                    $existingRedirect->update([
                        'is_active' => true,
                        'notes' => $this->redirectNotes($candidate),
                    ]);
                }

                $report['reused']++;
                $report['items'][] = [
                    'wordpress_id' => $candidate['reference'],
                    'status' => 'hergebruikt',
                    'message' => "Redirect {$existingRedirect->getKey()}: {$sourcePath} → {$candidate['target_url']}",
                ];
                $rows[] = $this->candidateRow($candidate, $existingRedirect, 'reused');

                if (! $dryRun) {
                    $this->setMapping($manifest, $candidate, $existingRedirect);
                }

                continue;
            }

            $redirect = DB::transaction(fn (): Redirect => Redirect::query()->create([
                'source_path' => $candidate['source_path'],
                'target_url' => $candidate['target_url'],
                'status_code' => $candidate['status_code'],
                'is_active' => $candidate['review_status'] !== 'pending',
                'notes' => $this->redirectNotes($candidate),
            ]));

            $this->setMapping($manifest, $candidate, $redirect);
            $report['imported']++;
            $report['items'][] = [
                'wordpress_id' => $candidate['reference'],
                'status' => 'geïmporteerd',
                'message' => "Redirect {$redirect->getKey()}: {$sourcePath} → {$candidate['target_url']}",
            ];
            $rows[] = $this->candidateRow($candidate, $redirect, 'imported');
        }

        if (! $dryRun) {
            $this->writeManifest($manifestPath, $manifest);
            $this->writeReport($reportPath, $report, $rows);
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
     * @return list<array<string, mixed>>
     */
    private function postCandidates(array $manifest): array
    {
        $candidates = [];

        foreach ((array) Arr::get($manifest, 'mappings.posts', []) as $wordpressId => $mapping) {
            if (! is_array($mapping)) {
                throw new InvalidArgumentException("Postmapping {$wordpressId} is ongeldig.");
            }

            $articleId = Arr::get($mapping, 'article_id');
            $sourceUrl = Arr::get($mapping, 'source_url');
            $article = is_int($articleId) ? Article::query()->find($articleId) : null;

            if (! $article instanceof Article || ! is_string($sourceUrl)) {
                throw new InvalidArgumentException("Postmapping {$wordpressId} mist een bestaand Article of een bron-URL.");
            }

            $candidates[] = $this->candidate(
                reference: 'post:'.$wordpressId,
                source: $sourceUrl,
                targetUrl: '/news/'.$article->slug,
                statusCode: 301,
                reviewStatus: 'not_required',
                reviewNotes: null,
                provenance: 'post mapping',
            );
        }

        return $candidates;
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @param  RedirectReport  $report
     * @return list<array<string, mixed>>
     */
    private function pageCandidates(array $manifest, array &$report): array
    {
        $candidates = [];

        foreach ((array) Arr::get($manifest, 'mappings.pages', []) as $wordpressId => $mapping) {
            if (! is_array($mapping)) {
                throw new InvalidArgumentException("Paginamapping {$wordpressId} is ongeldig.");
            }

            $sourceUrl = Arr::get($mapping, 'source_url');
            $decision = Arr::get($mapping, 'decision');
            $targetType = Arr::get($mapping, 'target.type');
            $targetPath = Arr::get($mapping, 'target.path');

            if (! is_string($sourceUrl)) {
                throw new InvalidArgumentException("Paginamapping {$wordpressId} mist een bron-URL.");
            }

            if (in_array($decision, ['gone', 'skip'], true)) {
                $report['skipped']++;
                $report['items'][] = [
                    'wordpress_id' => 'page:'.$wordpressId,
                    'status' => 'overgeslagen',
                    'message' => $decision === 'gone'
                        ? 'Expliciete 410-beslissing; er wordt geen Redirect-record aangemaakt.'
                        : 'Expliciet overgeslagen in de paginamapping.',
                ];

                continue;
            }

            if (! in_array($targetType, ['route', 'location', 'manual'], true) || ! is_string($targetPath)) {
                throw new InvalidArgumentException("Paginamapping {$wordpressId} mist een ondersteund redirectdoel.");
            }

            $candidate = $this->candidate(
                reference: 'page:'.$wordpressId,
                source: $sourceUrl,
                targetUrl: $targetPath,
                statusCode: 301,
                reviewStatus: 'not_required',
                reviewNotes: null,
                provenance: 'page mapping',
            );

            if ($this->isSelfRedirect($candidate['source_path'], $candidate['target_url'])) {
                $report['skipped']++;
                $report['items'][] = [
                    'wordpress_id' => 'page:'.$wordpressId,
                    'status' => 'overgeslagen',
                    'message' => 'Bronpad is al het canonieke doel; er is geen Redirect-record nodig.',
                ];

                continue;
            }

            $candidates[] = $candidate;
        }

        return $candidates;
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @param  RedirectReport  $report
     * @return list<array<string, mixed>>
     */
    private function explicitCandidates(array $manifest, array &$report): array
    {
        $redirects = Arr::get($manifest, 'redirects', []);

        if (! is_array($redirects) || ! array_is_list($redirects)) {
            throw new InvalidArgumentException('Het manifestveld redirects moet een lijst zijn.');
        }

        $candidates = [];

        foreach ($redirects as $index => $redirect) {
            if (! is_array($redirect)) {
                throw new InvalidArgumentException("Redirectselectie {$index} moet een JSON-object zijn.");
            }

            $reference = 'extra:'.($index + 1);
            $decision = Arr::get($redirect, 'decision', 'import');
            $reason = Arr::get($redirect, 'reason');

            if (! in_array($decision, ['import', 'skip'], true)) {
                throw new InvalidArgumentException("Redirectselectie {$reference} heeft een ongeldige decision.");
            }

            if ($decision === 'skip') {
                if (! is_string($reason) || Str::squish($reason) === '') {
                    throw new InvalidArgumentException("Redirectselectie {$reference} mist een skipreden.");
                }

                $report['skipped']++;
                $report['items'][] = [
                    'wordpress_id' => $reference,
                    'status' => 'overgeslagen',
                    'message' => Str::squish($reason),
                ];

                continue;
            }

            $source = Arr::get($redirect, 'source_url', Arr::get($redirect, 'source_path'));
            $targetUrl = Arr::get($redirect, 'target_url');
            $statusCode = Arr::get($redirect, 'status_code', 301);
            $reviewStatus = Arr::get($redirect, 'review.status', 'pending');
            $reviewNotes = Arr::get($redirect, 'review.notes');
            $provenance = Arr::get($redirect, 'provenance', 'manifest inventory');

            if (! is_string($source) || ! is_string($targetUrl)) {
                throw new InvalidArgumentException("Redirectselectie {$reference} mist source_url/source_path of target_url.");
            }

            if (! in_array($statusCode, [301, 302], true)) {
                throw new InvalidArgumentException("Redirectselectie {$reference} heeft een ongeldige status_code.");
            }

            if (! in_array($reviewStatus, ['pending', 'approved'], true)) {
                throw new InvalidArgumentException("Redirectselectie {$reference} heeft een ongeldige review.status.");
            }

            if ($reviewNotes !== null && ! is_string($reviewNotes)) {
                throw new InvalidArgumentException("Redirectselectie {$reference} heeft ongeldige review.notes.");
            }

            if (! is_string($provenance) || Str::squish($provenance) === '') {
                throw new InvalidArgumentException("Redirectselectie {$reference} heeft ongeldige provenance.");
            }

            $candidates[] = $this->candidate(
                reference: $reference,
                source: $source,
                targetUrl: $targetUrl,
                statusCode: $statusCode,
                reviewStatus: $reviewStatus,
                reviewNotes: is_string($reviewNotes) ? Str::squish($reviewNotes) : null,
                provenance: Str::squish($provenance),
            );
        }

        return $candidates;
    }

    /** @return array<string, mixed> */
    private function candidate(
        string $reference,
        string $source,
        string $targetUrl,
        int $statusCode,
        string $reviewStatus,
        ?string $reviewNotes,
        string $provenance,
    ): array {
        return [
            'reference' => $reference,
            'source_path' => $this->sourcePath($source),
            'target_url' => $this->targetUrl($targetUrl),
            'status_code' => $statusCode,
            'review_status' => $reviewStatus,
            'review_notes' => $reviewNotes,
            'provenance' => $provenance,
        ];
    }

    private function sourcePath(string $source): string
    {
        $parts = parse_url($source);

        if ($parts === false || (isset($parts['scheme']) && ! in_array($parts['scheme'], ['http', 'https'], true))) {
            throw new InvalidArgumentException("Ongeldige redirectbron: {$source}");
        }

        $path = isset($parts['path']) ? $parts['path'] : $source;

        if (! str_starts_with($path, '/')) {
            throw new InvalidArgumentException("Redirectbron moet een absolute URL of absoluut pad zijn: {$source}");
        }

        return Redirect::normalizePath($path);
    }

    private function targetUrl(string $targetUrl): string
    {
        if (! str_starts_with($targetUrl, '/') || str_starts_with($targetUrl, '//')) {
            throw new InvalidArgumentException("Redirectdoel moet een lokaal absoluut pad zijn: {$targetUrl}");
        }

        $parts = parse_url($targetUrl);

        if ($parts === false || isset($parts['host']) || isset($parts['fragment'])) {
            throw new InvalidArgumentException("Ongeldig redirectdoel: {$targetUrl}");
        }

        $path = Redirect::normalizePath($parts['path'] ?? '/');
        $query = isset($parts['query']) && $parts['query'] !== '' ? '?'.$parts['query'] : '';

        return $path.$query;
    }

    /**
     * @param  list<array<string, mixed>>  $candidates
     * @return array<string, list<array<string, mixed>>>
     */
    private function groupCandidates(array $candidates): array
    {
        $grouped = [];

        foreach ($candidates as $candidate) {
            $grouped[$candidate['source_path']][] = $candidate;
        }

        ksort($grouped);

        return $grouped;
    }

    /**
     * @param  list<array<string, mixed>>  $candidates
     * @return array<string, mixed>
     */
    private function mergeCandidates(array $candidates): array
    {
        $candidate = $candidates[0];
        $candidate['reference'] = implode(', ', array_unique(array_column($candidates, 'reference')));
        $candidate['provenance'] = implode(', ', array_unique(array_column($candidates, 'provenance')));

        if (in_array('pending', array_column($candidates, 'review_status'), true)) {
            $candidate['review_status'] = 'pending';
        }

        $notes = array_values(array_unique(array_filter(array_column($candidates, 'review_notes'), 'is_string')));
        $candidate['review_notes'] = $notes === [] ? null : implode(' ', $notes);

        return $candidate;
    }

    private function isSelfRedirect(string $sourcePath, string $targetUrl): bool
    {
        $targetPath = Redirect::normalizePath((string) parse_url($targetUrl, PHP_URL_PATH));
        $targetQuery = parse_url($targetUrl, PHP_URL_QUERY);

        return $sourcePath === $targetPath && ($targetQuery === null || $targetQuery === '');
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @param  array<string, mixed>  $candidate
     */
    private function shouldActivateApprovedRedirect(
        array $manifest,
        array $candidate,
        Redirect $redirect,
    ): bool {
        if ($candidate['review_status'] !== 'approved' || $redirect->is_active) {
            return false;
        }

        $key = hash('sha256', $candidate['source_path']);
        $mapping = Arr::get($manifest, "mappings.redirects.{$key}");

        return is_array($mapping)
            && Arr::get($mapping, 'redirect_id') === $redirect->getKey()
            && Arr::get($mapping, 'review.status') === 'pending';
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @param  array<string, mixed>  $candidate
     */
    private function setMapping(array &$manifest, array $candidate, Redirect $redirect): void
    {
        $key = hash('sha256', $candidate['source_path']);
        $mappings = Arr::get($manifest, 'mappings');
        $mappings = is_array($mappings) ? $mappings : [];
        $redirectMappings = Arr::get($mappings, 'redirects');
        $redirectMappings = is_array($redirectMappings) ? $redirectMappings : [];
        $redirectMappings[$key] = [
            'redirect_id' => $redirect->getKey(),
            'source_path' => $candidate['source_path'],
            'target_url' => $candidate['target_url'],
            'status_code' => $candidate['status_code'],
            'provenance' => explode(', ', $candidate['provenance']),
            'review' => [
                'status' => $candidate['review_status'],
                'notes' => $candidate['review_notes'],
            ],
            'imported_at' => now()->toIso8601String(),
        ];
        $mappings['redirects'] = $redirectMappings;
        $manifest['mappings'] = $mappings;
    }

    /** @param array<string, mixed> $candidate */
    private function redirectNotes(array $candidate): string
    {
        $notes = "WordPress import; provenance: {$candidate['provenance']}; review: {$candidate['review_status']}.";

        return $candidate['review_notes'] === null ? $notes : $notes.' '.$candidate['review_notes'];
    }

    /**
     * @param  array<string, mixed>  $candidate
     * @return array<string, mixed>
     */
    private function candidateRow(array $candidate, ?Redirect $redirect, string $result): array
    {
        return [
            'source_path' => $candidate['source_path'],
            'target_url' => $candidate['target_url'],
            'status_code' => $candidate['status_code'],
            'active' => $redirect instanceof Redirect
                ? $redirect->is_active
                : $candidate['review_status'] !== 'pending',
            'review' => $candidate['review_status'],
            'provenance' => $candidate['provenance'],
            'result' => $result,
        ];
    }

    /** @return array<string, mixed> */
    private function conflictRow(string $sourcePath, string $targetUrl): array
    {
        return [
            'source_path' => $sourcePath,
            'target_url' => $targetUrl,
            'status_code' => 0,
            'active' => false,
            'review' => 'conflict',
            'provenance' => 'multiple or existing',
            'result' => 'blocked',
        ];
    }

    /** @param array<string, mixed> $manifest */
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
     * @param  RedirectReport  $report
     * @param  list<array<string, mixed>>  $rows
     */
    private function writeReport(string $reportPath, array $report, array $rows): void
    {
        File::ensureDirectoryExists(dirname($reportPath));
        $lines = [
            '# WordPress Redirect Import Review',
            '',
            "Selected: {$report['selected']}; imported: {$report['imported']}; reused: {$report['reused']}; skipped: {$report['skipped']}; conflicts: {$report['conflicts']}; pending review: {$report['pending_review']}.",
            '',
            '| Source | Target | Status | Active | Review | Provenance | Result |',
            '| --- | --- | ---: | --- | --- | --- | --- |',
        ];

        foreach ($rows as $row) {
            $lines[] = sprintf(
                '| %s | %s | %s | %s | %s | %s | %s |',
                $this->markdownCell((string) $row['source_path']),
                $this->markdownCell((string) $row['target_url']),
                $row['status_code'] === 0 ? '—' : (string) $row['status_code'],
                $row['active'] ? 'yes' : 'no',
                $row['review'],
                $this->markdownCell((string) $row['provenance']),
                $row['result'],
            );
        }

        $lines[] = '';
        $lines[] = '## Manual decisions';
        $lines[] = '';
        $manualRows = array_filter($rows, fn (array $row): bool => in_array($row['review'], ['pending', 'conflict'], true));

        if ($manualRows === []) {
            $lines[] = '- None.';
        } else {
            foreach ($manualRows as $row) {
                $lines[] = "- {$row['source_path']} → {$row['target_url']}: {$row['review']}.";
            }
        }

        if ($report['failed'] > 0) {
            $lines[] = '';
            $lines[] = '## Blocking conflicts';
            $lines[] = '';

            foreach ($report['items'] as $item) {
                if ($item['status'] === 'conflict') {
                    $lines[] = "- {$item['wordpress_id']}: {$item['message']}";
                }
            }
        }

        $contents = implode(PHP_EOL, $lines).PHP_EOL;

        if (File::isFile($reportPath)) {
            File::replace($reportPath, $contents);

            return;
        }

        if (File::put($reportPath, $contents) === false) {
            throw new RuntimeException("Kon redirectreviewrapport niet schrijven: {$reportPath}");
        }
    }

    private function markdownCell(string $value): string
    {
        return str_replace('|', '\\|', $value);
    }

    /** @param RedirectReport $report */
    private function addConflict(array &$report, string $sourcePath, string $message): void
    {
        $report['failed']++;
        $report['conflicts']++;
        $report['items'][] = [
            'wordpress_id' => $sourcePath,
            'status' => 'conflict',
            'message' => $message,
        ];
    }

    /** @return RedirectReport */
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
            'conflicts' => 0,
            'pending_review' => 0,
            'report_path' => $reportPath,
        ];
    }
}
