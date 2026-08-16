<?php

namespace App\Actions;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use JsonException;
use RuntimeException;

final class RecordWordPressRehearsal
{
    /**
     * @param  array<string, array<string, mixed>>  $firstPass
     * @param  array<string, array<string, mixed>>  $secondPass
     * @param  array<string, int>  $firstCounts
     * @param  array<string, int>  $secondCounts
     * @param  list<array<string, mixed>>  $samples
     * @param  list<array{name: string, path: string, exists: bool}>  $artifacts
     * @return array{status: string, blockers: list<array{id: string, message: string}>}
     *
     * @throws JsonException
     */
    public function handle(
        string $manifestPath,
        string $reportPath,
        string $baseUrl,
        array $firstPass,
        array $secondPass,
        array $firstCounts,
        array $secondCounts,
        array $samples,
        array $artifacts,
        int $importReportExitCode,
        bool $manualReviewApproved,
    ): array {
        $manifest = $this->readManifest($manifestPath);
        $blockers = $this->blockers(
            firstPass: $firstPass,
            secondPass: $secondPass,
            firstCounts: $firstCounts,
            secondCounts: $secondCounts,
            samples: $samples,
            artifacts: $artifacts,
            importReportExitCode: $importReportExitCode,
            manualReviewApproved: $manualReviewApproved,
        );
        $status = $blockers === [] ? 'ready' : 'blocked';
        $rehearsal = [
            'status' => $status,
            'environment' => app()->environment(),
            'base_url' => $baseUrl,
            'first_pass' => $firstPass,
            'second_pass' => $secondPass,
            'persistent_counts' => [
                'after_first_pass' => $firstCounts,
                'after_second_pass' => $secondCounts,
            ],
            'samples' => $samples,
            'artifacts' => $artifacts,
            'import_report_exit_code' => $importReportExitCode,
            'manual_review_approved' => $manualReviewApproved,
            'blockers' => $blockers,
            'completed_at' => now()->toIso8601String(),
        ];
        Arr::set($manifest, 'rehearsal', $rehearsal);

        File::replace(
            $manifestPath,
            json_encode(
                $manifest,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
            ).PHP_EOL,
        );
        $this->writeReport($reportPath, $rehearsal);

        return ['status' => $status, 'blockers' => $blockers];
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
     * @param  array<string, array<string, mixed>>  $firstPass
     * @param  array<string, array<string, mixed>>  $secondPass
     * @param  array<string, int>  $firstCounts
     * @param  array<string, int>  $secondCounts
     * @param  list<array<string, mixed>>  $samples
     * @param  list<array{name: string, path: string, exists: bool}>  $artifacts
     * @return list<array{id: string, message: string}>
     */
    private function blockers(
        array $firstPass,
        array $secondPass,
        array $firstCounts,
        array $secondCounts,
        array $samples,
        array $artifacts,
        int $importReportExitCode,
        bool $manualReviewApproved,
    ): array {
        $messages = [];

        foreach (['first' => $firstPass, 'second' => $secondPass] as $passName => $pass) {
            foreach ($pass as $phase => $run) {
                if ((int) Arr::get($run, 'exit_code', 1) !== 0) {
                    $messages[] = "De {$passName} pass faalde in fase {$phase}.";
                }
            }
        }

        foreach ($secondPass as $phase => $run) {
            if ((int) Arr::get($run, 'imported', 0) > 0) {
                $messages[] = "De tweede pass importeerde opnieuw records in fase {$phase}; idempotentie is niet bewezen.";
            }
        }

        foreach ($firstCounts as $model => $count) {
            if (($secondCounts[$model] ?? -1) !== $count) {
                $messages[] = "Het aantal {$model} wijzigde tussen pass één ({$count}) en pass twee ({$secondCounts[$model]}).";
            }
        }

        foreach ($samples as $sample) {
            if (Arr::get($sample, 'passed') !== true) {
                $messages[] = 'Publieke sample '.Arr::get($sample, 'type').' '.Arr::get($sample, 'reference').' faalde: '.Arr::get($sample, 'actual').'.';
            }
        }

        foreach ($artifacts as $artifact) {
            if (! $artifact['exists']) {
                $messages[] = "Reviewartefact {$artifact['name']} ontbreekt op {$artifact['path']}.";
            }
        }

        if ($importReportExitCode !== 0) {
            $messages[] = 'Het geconsolideerde importreviewrapport staat op BLOCKED.';
        }

        if (! $manualReviewApproved) {
            $messages[] = 'Visuele publieke samples en de beheerreview zijn nog niet handmatig goedgekeurd.';
        }

        $messages = array_values(array_unique($messages));

        return array_map(
            fn (string $message, int $index): array => [
                'id' => sprintf('DDS-022-B%03d', $index + 1),
                'message' => $message,
            ],
            $messages,
            array_keys($messages),
        );
    }

    /** @param array<string, mixed> $rehearsal */
    private function writeReport(string $reportPath, array $rehearsal): void
    {
        File::ensureDirectoryExists(dirname($reportPath));
        $status = strtoupper((string) $rehearsal['status']);
        $lines = [
            '# WordPress Staging Rehearsal',
            '',
            "Rehearsal status: **{$status}**",
            '',
            '- Environment: `'.$rehearsal['environment'].'`',
            '- Base URL: '.$rehearsal['base_url'],
            '- Completed: '.$rehearsal['completed_at'],
            '- Manual visual/admin review: '.($rehearsal['manual_review_approved'] ? 'approved' : 'pending'),
            '',
            '## Two-pass import evidence',
            '',
            '| Pass | Phase | Exit | Selected | Imported | Reused | Failed |',
            '| --- | --- | ---: | ---: | ---: | ---: | ---: |',
        ];

        foreach (['first' => $rehearsal['first_pass'], 'second' => $rehearsal['second_pass']] as $passName => $pass) {
            foreach ($pass as $phase => $run) {
                $lines[] = "| {$passName} | {$phase} | {$run['exit_code']} | {$run['selected']} | {$run['imported']} | {$run['reused']} | {$run['failed']} |";
            }
        }

        $lines[] = '';
        $lines[] = '## Persistent record counts';
        $lines[] = '';
        $lines[] = '| Model | After pass one | After pass two | Stable |';
        $lines[] = '| --- | ---: | ---: | --- |';
        $firstCounts = Arr::get($rehearsal, 'persistent_counts.after_first_pass', []);
        $secondCounts = Arr::get($rehearsal, 'persistent_counts.after_second_pass', []);

        foreach ($firstCounts as $model => $count) {
            $secondCount = $secondCounts[$model] ?? '—';
            $lines[] = "| {$model} | {$count} | {$secondCount} | ".($count === $secondCount ? 'yes' : 'no').' |';
        }

        $lines[] = '';
        $lines[] = '## Public sample checks';
        $lines[] = '';
        $lines[] = '| Type | Reference | URL | Expected | Actual | Result |';
        $lines[] = '| --- | --- | --- | --- | --- | --- |';

        foreach ($rehearsal['samples'] as $sample) {
            $lines[] = '| '.$sample['type'].' | '.$sample['reference'].' | '.$sample['url'].' | '.$sample['expected'].' | '.$sample['actual'].' | '.($sample['passed'] ? 'pass' : 'fail').' |';
        }

        $lines[] = '';
        $lines[] = '## Review artifacts';
        $lines[] = '';

        foreach ($rehearsal['artifacts'] as $artifact) {
            $lines[] = '- '.($artifact['exists'] ? '[x]' : '[ ]')." {$artifact['name']}: {$artifact['path']}";
        }

        $lines[] = '';
        $lines[] = '## Concrete blockers';
        $lines[] = '';

        if ($rehearsal['blockers'] === []) {
            $lines[] = '- None.';
        } else {
            foreach ($rehearsal['blockers'] as $blocker) {
                $lines[] = "- {$blocker['id']}: {$blocker['message']}";
            }
        }

        $contents = implode(PHP_EOL, $lines).PHP_EOL;

        if (File::isFile($reportPath)) {
            File::replace($reportPath, $contents);

            return;
        }

        if (File::put($reportPath, $contents) === false) {
            throw new RuntimeException("Kon rehearsalrapport niet schrijven: {$reportPath}");
        }
    }
}
