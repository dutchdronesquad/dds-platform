<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

#[Signature('dds:cache-release-version')]
#[Description('Cache the unique release tag pointing to the current commit during the application build.')]
final class CacheReleaseVersionCommand extends Command
{
    public function handle(): int
    {
        $result = Process::path(base_path())->run(['git', 'tag', '--points-at', 'HEAD', '--list', 'v*']);

        if ($result->failed()) {
            $this->error('Cannot read Git tags. Run this command during the build with the Git repository available.');

            return self::FAILURE;
        }

        $tags = array_values(array_filter(
            explode("\n", trim($result->output())),
            fn (string $tag): bool => preg_match('/\Av(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\z/', $tag) === 1,
        ));

        if (count($tags) !== 1) {
            $this->error('The current commit must have exactly one stable release tag (vMAJOR.MINOR.PATCH).');

            return self::FAILURE;
        }

        File::replace(app()->bootstrapPath('cache/release-version.php'), '<?php return '.var_export($tags[0], true).";\n");

        $this->info("Cached release version {$tags[0]}. Run config:cache afterwards if configuration is cached.");

        return self::SUCCESS;
    }
}
