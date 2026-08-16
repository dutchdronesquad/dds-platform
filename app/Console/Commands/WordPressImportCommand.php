<?php

namespace App\Console\Commands;

use App\Actions\CleanWordPressImportedContent;
use App\Actions\GenerateWordPressImportReport;
use App\Actions\ImportWordPressMedia;
use App\Actions\ImportWordPressPosts;
use App\Actions\ImportWordPressRedirects;
use App\Actions\MapWordPressPages;
use App\Actions\RecordWordPressImportRun;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use InvalidArgumentException;
use JsonException;

#[Signature('wordpress:import
    {phase : Import phase (media, posts, pages, cleanup, redirects, or report)}
    {--manifest=storage/app/imports/wordpress/selection.json : Path to the temporary selection manifest}
    {--report= : Output path for the phase review report}
    {--dry-run : Inspect selected records without writing files, records, or mappings}
    {--refresh-source : Re-run cleanup from the verified WordPress source when the previous cleanup output is still unchanged}')]
#[Description('Run a temporary, repeatable WordPress import phase.')]
final class WordPressImportCommand extends Command
{
    public function handle(
        ImportWordPressMedia $importWordPressMedia,
        ImportWordPressPosts $importWordPressPosts,
        MapWordPressPages $mapWordPressPages,
        CleanWordPressImportedContent $cleanWordPressImportedContent,
        ImportWordPressRedirects $importWordPressRedirects,
        GenerateWordPressImportReport $generateWordPressImportReport,
        RecordWordPressImportRun $recordWordPressImportRun,
    ): int {
        $phase = (string) $this->argument('phase');

        if (! in_array($phase, ['media', 'posts', 'pages', 'cleanup', 'redirects', 'report'], true)) {
            $this->error('Alleen de importfasen "media", "posts", "pages", "cleanup", "redirects" en "report" zijn momenteel beschikbaar.');

            return self::FAILURE;
        }

        $manifestPath = $this->manifestPath();

        try {
            $report = match ($phase) {
                'media' => $importWordPressMedia->handle(
                    manifestPath: $manifestPath,
                    dryRun: (bool) $this->option('dry-run'),
                ),
                'posts' => $importWordPressPosts->handle(
                    manifestPath: $manifestPath,
                    dryRun: (bool) $this->option('dry-run'),
                ),
                'pages' => $mapWordPressPages->handle(
                    manifestPath: $manifestPath,
                    reportPath: $this->reportPath($manifestPath, $phase),
                    dryRun: (bool) $this->option('dry-run'),
                ),
                'cleanup' => $cleanWordPressImportedContent->handle(
                    manifestPath: $manifestPath,
                    reportPath: $this->reportPath($manifestPath, $phase),
                    dryRun: (bool) $this->option('dry-run'),
                    refreshSource: (bool) $this->option('refresh-source'),
                ),
                'redirects' => $importWordPressRedirects->handle(
                    manifestPath: $manifestPath,
                    reportPath: $this->reportPath($manifestPath, $phase),
                    dryRun: (bool) $this->option('dry-run'),
                ),
                'report' => $generateWordPressImportReport->handle(
                    manifestPath: $manifestPath,
                    reportPath: $this->reportPath($manifestPath, $phase),
                    dryRun: (bool) $this->option('dry-run'),
                ),
            };

            if (! (bool) $this->option('dry-run') && $phase !== 'report') {
                $recordWordPressImportRun->handle($manifestPath, $phase, $report);
            }
        } catch (InvalidArgumentException|JsonException|\RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ((bool) $this->option('dry-run')) {
            $this->components->info('Dry-run: er zijn geen bestanden, records of manifestmappings gewijzigd.');
        }

        $this->table(
            ['WordPress ID', 'Resultaat', 'Details'],
            array_map(
                fn (array $item): array => [
                    (string) $item['wordpress_id'],
                    $item['status'],
                    $item['message'],
                ],
                $report['items'],
            ),
        );

        $this->newLine();
        $this->line(sprintf(
            'Geselecteerd: %d | Klaar: %d | Geïmporteerd: %d | Hergebruikt: %d | Overgeslagen: %d | Mislukt: %d',
            $report['selected'],
            $report['ready'],
            $report['imported'],
            $report['reused'],
            $report['skipped'],
            $report['failed'],
        ));

        if (($report['missing_alt_text'] ?? 0) > 0) {
            $this->warn("Ontbrekende alt-tekst: {$report['missing_alt_text']} geselecteerde afbeelding(en).");
        }

        if (($report['unresolved_links'] ?? 0) > 0) {
            $this->warn("Onopgeloste interne links: {$report['unresolved_links']}.");
        }

        if (($report['missing_media'] ?? 0) > 0) {
            $this->warn("Ontbrekende geïmporteerde media: {$report['missing_media']}.");
        }

        if (($report['suspicious_markup'] ?? 0) > 0) {
            $this->warn("Verdachte markup: {$report['suspicious_markup']} melding(en).");
        }

        if (($report['conflicts'] ?? 0) > 0) {
            $this->warn("Redirectconflicten: {$report['conflicts']}.");
        }

        if (($report['pending_review'] ?? 0) > 0) {
            $this->warn("Redirects in afwachting van review: {$report['pending_review']}.");
        }

        if (! (bool) $this->option('dry-run')) {
            if ($phase !== 'report') {
                $this->line("Manifest bijgewerkt: {$manifestPath}");
            }

            if (isset($report['report_path'])) {
                $this->line("Reviewrapport bijgewerkt: {$report['report_path']}");
            }
        }

        return $report['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function manifestPath(): string
    {
        $manifestPath = (string) $this->option('manifest');

        return str_starts_with($manifestPath, DIRECTORY_SEPARATOR)
            ? $manifestPath
            : base_path($manifestPath);
    }

    private function reportPath(string $manifestPath, string $phase): string
    {
        $reportPath = $this->option('report');

        if (! is_string($reportPath) || $reportPath === '') {
            $filename = match ($phase) {
                'cleanup' => 'cleanup-review.md',
                'redirects' => 'redirect-review.md',
                'report' => 'import-review.md',
                default => 'page-review.md',
            };

            return dirname($manifestPath).DIRECTORY_SEPARATOR.$filename;
        }

        return str_starts_with($reportPath, DIRECTORY_SEPARATOR)
            ? $reportPath
            : base_path($reportPath);
    }
}
