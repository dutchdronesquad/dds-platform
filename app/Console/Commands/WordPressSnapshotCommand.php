<?php

namespace App\Console\Commands;

use App\Actions\CreateWordPressSourceSnapshot;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use InvalidArgumentException;
use JsonException;
use RuntimeException;

#[Signature('wordpress:snapshot
    {--manifest=storage/app/imports/wordpress/selection.json : Path to the temporary selection manifest}
    {--xml=storage/app/imports/wordpress/dutchdronesquad.WordPress.2026-08-08.xml : Path to the WordPress XML export}
    {--output=storage/app/imports/wordpress/source-inventory : Destination for the self-contained source bundle}
    {--force : Replace the exact output directory when it already exists}')]
#[Description('Capture WordPress REST data and media in a verified offline source bundle.')]
final class WordPressSnapshotCommand extends Command
{
    public function handle(CreateWordPressSourceSnapshot $createSnapshot): int
    {
        $manifestPath = $this->absolutePath((string) $this->option('manifest'));
        $xmlPath = $this->absolutePath((string) $this->option('xml'));
        $outputDirectory = $this->absolutePath((string) $this->option('output'));

        try {
            $result = $createSnapshot->handle(
                manifestPath: $manifestPath,
                outputDirectory: $outputDirectory,
                xmlPath: $xmlPath,
                force: (bool) $this->option('force'),
            );
        } catch (InvalidArgumentException|JsonException|RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->success('Zelfstandige WordPress bronbundel is compleet.');
        $this->table(
            ['Posts', 'Pagina’s', 'Mediarecords', 'Mediabestanden', 'Bytes'],
            [[
                $result['posts'],
                $result['pages'],
                $result['media'],
                $result['media_files'],
                $result['bytes'],
            ]],
        );
        $this->line("Bronbundel: {$result['directory']}");
        $this->line("Manifest gebruikt voortaan de lokale bronbundel: {$manifestPath}");

        return self::SUCCESS;
    }

    private function absolutePath(string $path): string
    {
        return str_starts_with($path, DIRECTORY_SEPARATOR) ? $path : base_path($path);
    }
}
