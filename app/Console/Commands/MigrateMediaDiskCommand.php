<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[Signature('dds:migrate-media-disk
    {--from=public : The disk media currently lives on}
    {--to=s3 : The disk to copy media to}
    {--delete-source : Remove the files from the source disk after a successful copy}
    {--force : Allow execution in the production environment}')]
#[Description('Copy existing media library files from one disk to another and update their disk records.')]
final class MigrateMediaDiskCommand extends Command
{
    public function handle(): int
    {
        if (app()->environment('production') && ! (bool) $this->option('force')) {
            $this->error('This migration does not run in production without --force.');

            return self::FAILURE;
        }

        $from = (string) $this->option('from');
        $to = (string) $this->option('to');

        if ($from === $to) {
            $this->error('--from and --to must be different disks.');

            return self::FAILURE;
        }

        $mediaItems = Media::query()->where('disk', $from)->get();

        if ($mediaItems->isEmpty()) {
            $this->info("No media found on disk [{$from}].");

            return self::SUCCESS;
        }

        $sourceDisk = Storage::disk($from);
        $targetDisk = Storage::disk($to);

        $this->info("Migrating {$mediaItems->count()} media item(s) from [{$from}] to [{$to}]...");

        $progressBar = $this->output->createProgressBar($mediaItems->count());
        $progressBar->start();

        foreach ($mediaItems as $mediaItem) {
            $directory = rtrim(dirname($mediaItem->getPathRelativeToRoot()), '/');

            foreach ($sourceDisk->allFiles($directory) as $file) {
                $stream = $sourceDisk->readStream($file);

                if ($stream === null) {
                    continue;
                }

                $targetDisk->put($file, $stream);

                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            $mediaItem->forceFill([
                'disk' => $to,
                'conversions_disk' => $mediaItem->conversions_disk === $from ? $to : $mediaItem->conversions_disk,
            ])->save();

            if ($this->option('delete-source')) {
                $sourceDisk->deleteDirectory($directory);
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);
        $this->components->success("Migration complete: {$mediaItems->count()} media item(s) now on [{$to}].");

        return self::SUCCESS;
    }
}
