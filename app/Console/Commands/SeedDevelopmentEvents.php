<?php

namespace App\Console\Commands;

use Database\Seeders\DevelopmentEventSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class SeedDevelopmentEvents extends Command
{
    /** @var string */
    protected $signature = 'dds:seed-demo-events
        {--reset : Remove the demo event dataset instead of recreating it}';

    /** @var string */
    protected $description = 'Create or remove the deterministic local DDS event dataset.';

    public function handle(DevelopmentEventSeeder $seeder): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('De DDS demo-events mogen alleen lokaal worden beheerd.');

            return self::FAILURE;
        }

        if ($this->option('reset')) {
            $deletedEvents = $seeder->reset();
            $this->info("{$deletedEvents} demo-events verwijderd; overige content is behouden.");

            return self::SUCCESS;
        }

        if (app()->environment('local') && ! File::exists(public_path('storage'))) {
            $this->call('storage:link');
        }

        $seeder->run();
        $this->info(count(DevelopmentEventSeeder::EVENT_SLUGS).' demo-events zijn aangemaakt of bijgewerkt.');

        return self::SUCCESS;
    }
}
