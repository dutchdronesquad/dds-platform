<?php

namespace App\Console\Commands;

use App\Actions\RecordWordPressRehearsal;
use App\Actions\VerifyWordPressStagingSamples;
use App\Models\Article;
use App\Models\Event;
use App\Models\Location;
use App\Models\MediaAsset;
use App\Models\Redirect;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use JsonException;
use RuntimeException;

#[Signature('wordpress:rehearse
    {--manifest=storage/app/imports/wordpress/selection.json : Path to the staging selection manifest}
    {--base-url= : Public staging base URL; defaults to APP_URL}
    {--report= : Output path for the rehearsal evidence report}
    {--import-report= : Output path for the consolidated import review}
    {--approve-manual-review : Confirm visual public samples and admin reports were manually reviewed}
    {--force : Allow execution in the production environment}')]
#[Description('Run and verify the complete WordPress import twice in staging.')]
final class WordPressRehearsalCommand extends Command
{
    /** @var list<string> */
    private const array PHASES = ['media', 'posts', 'pages', 'cleanup', 'redirects'];

    public function handle(
        VerifyWordPressStagingSamples $verifyWordPressStagingSamples,
        RecordWordPressRehearsal $recordWordPressRehearsal,
    ): int {
        if (app()->environment('production') && ! (bool) $this->option('force')) {
            $this->error('De rehearsal draait niet in production zonder --force.');

            return self::FAILURE;
        }

        $manifestPath = $this->absolutePath((string) $this->option('manifest'));

        if (! File::isFile($manifestPath)) {
            $this->error("Stagingmanifest niet gevonden: {$manifestPath}");

            return self::FAILURE;
        }

        $baseUrl = $this->baseUrl();

        if ($baseUrl === null) {
            $this->error('Geef met --base-url een geldige HTTP(S)-URL van de stagingomgeving op.');

            return self::FAILURE;
        }

        $rehearsalReportPath = $this->reportPath($manifestPath, 'rehearsal-review.md', 'report');
        $importReportPath = $this->reportPath($manifestPath, 'import-review.md', 'import-report');
        $passRows = [];
        $passes = [];
        $counts = [];

        try {
            foreach (['first', 'second'] as $passName) {
                $passes[$passName] = [];

                foreach (self::PHASES as $phase) {
                    $exitCode = $this->callSilently('wordpress:import', [
                        'phase' => $phase,
                        '--manifest' => $manifestPath,
                    ]);
                    $run = $this->latestRun($manifestPath, $phase);
                    $run['exit_code'] = $exitCode;
                    $passes[$passName][$phase] = $run;
                    $passRows[] = [
                        $passName,
                        $phase,
                        (string) $exitCode,
                        (string) $run['selected'],
                        (string) $run['imported'],
                        (string) $run['reused'],
                        (string) $run['failed'],
                    ];
                }

                $counts[$passName] = $this->persistentCounts();
            }

            $importReportExitCode = $this->callSilently('wordpress:import', [
                'phase' => 'report',
                '--manifest' => $manifestPath,
                '--report' => $importReportPath,
            ]);
            $samples = $verifyWordPressStagingSamples->handle($manifestPath, $baseUrl);
            $artifacts = $this->artifacts($manifestPath, $importReportPath);
            $result = $recordWordPressRehearsal->handle(
                manifestPath: $manifestPath,
                reportPath: $rehearsalReportPath,
                baseUrl: $baseUrl,
                firstPass: $passes['first'],
                secondPass: $passes['second'],
                firstCounts: $counts['first'],
                secondCounts: $counts['second'],
                samples: $samples,
                artifacts: $artifacts,
                importReportExitCode: $importReportExitCode,
                manualReviewApproved: (bool) $this->option('approve-manual-review'),
            );
        } catch (InvalidArgumentException|JsonException|RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['Pass', 'Fase', 'Exit', 'Geselecteerd', 'Geïmporteerd', 'Hergebruikt', 'Mislukt'],
            $passRows,
        );
        $this->newLine();
        $this->line('Publieke samples: '.count($samples).' | Geslaagd: '.count(array_filter($samples, fn (array $sample): bool => $sample['passed'] === true)));
        $this->line("Importreview: {$importReportPath}");
        $this->line("Rehearsalbewijs: {$rehearsalReportPath}");

        if ($result['status'] === 'blocked') {
            $this->warn('Rehearsalstatus: BLOCKED');

            foreach ($result['blockers'] as $blocker) {
                $this->line("{$blocker['id']}: {$blocker['message']}");
            }

            return self::FAILURE;
        }

        $this->components->success('Rehearsalstatus: READY; twee imports bleven idempotent en alle checks slaagden.');

        return self::SUCCESS;
    }

    /** @return array<string, mixed> */
    private function latestRun(string $manifestPath, string $phase): array
    {
        $manifest = json_decode(File::get($manifestPath), true, flags: JSON_THROW_ON_ERROR);
        $run = is_array($manifest) ? Arr::get($manifest, "runs.{$phase}") : null;

        return [
            'selected' => is_array($run) ? (int) Arr::get($run, 'selected', 0) : 0,
            'imported' => is_array($run) ? (int) Arr::get($run, 'imported', 0) : 0,
            'reused' => is_array($run) ? (int) Arr::get($run, 'reused', 0) : 0,
            'failed' => is_array($run) ? (int) Arr::get($run, 'failed', 1) : 1,
            'completed_at' => is_array($run) ? Arr::get($run, 'completed_at') : null,
        ];
    }

    /** @return array<string, int> */
    private function persistentCounts(): array
    {
        return [
            'articles' => Article::query()->count(),
            'media_assets' => MediaAsset::query()->count(),
            'redirects' => Redirect::query()->count(),
            'locations' => Location::query()->count(),
            'events' => Event::query()->count(),
        ];
    }

    /** @return list<array{name: string, path: string, exists: bool}> */
    private function artifacts(string $manifestPath, string $importReportPath): array
    {
        $directory = dirname($manifestPath);

        return array_map(
            fn (array $artifact): array => [
                ...$artifact,
                'exists' => File::isFile($artifact['path']),
            ],
            [
                ['name' => 'page mapping review', 'path' => $directory.DIRECTORY_SEPARATOR.'page-review.md'],
                ['name' => 'content cleanup review', 'path' => $directory.DIRECTORY_SEPARATOR.'cleanup-review.md'],
                ['name' => 'redirect review', 'path' => $directory.DIRECTORY_SEPARATOR.'redirect-review.md'],
                ['name' => 'consolidated import review', 'path' => $importReportPath],
            ],
        );
    }

    private function baseUrl(): ?string
    {
        $baseUrl = $this->option('base-url');
        $baseUrl = is_string($baseUrl) && $baseUrl !== '' ? $baseUrl : config('app.url');

        if (! is_string($baseUrl)
            || filter_var($baseUrl, FILTER_VALIDATE_URL) === false
            || ! in_array(parse_url($baseUrl, PHP_URL_SCHEME), ['http', 'https'], true)) {
            return null;
        }

        return rtrim($baseUrl, '/');
    }

    private function reportPath(string $manifestPath, string $defaultFilename, string $option): string
    {
        $path = $this->option($option);

        if (! is_string($path) || $path === '') {
            return dirname($manifestPath).DIRECTORY_SEPARATOR.$defaultFilename;
        }

        return $this->absolutePath($path);
    }

    private function absolutePath(string $path): string
    {
        return str_starts_with($path, DIRECTORY_SEPARATOR) ? $path : base_path($path);
    }
}
