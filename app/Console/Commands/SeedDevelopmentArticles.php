<?php

namespace App\Console\Commands;

use Database\Seeders\DevelopmentArticleSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class SeedDevelopmentArticles extends Command
{
    /** @var string */
    protected $signature = 'dds:seed-demo-articles
        {--reset : Remove the demo article dataset instead of recreating it}';

    /** @var string */
    protected $description = 'Create or remove the deterministic local DDS article dataset.';

    public function handle(DevelopmentArticleSeeder $seeder): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('De DDS demo-artikelen mogen alleen lokaal worden beheerd.');

            return self::FAILURE;
        }

        if ($this->option('reset')) {
            $deletedArticles = $seeder->reset();
            $this->info("{$deletedArticles} demo-artikelen verwijderd; overige content is behouden.");

            return self::SUCCESS;
        }

        if (app()->environment('local') && ! File::exists(public_path('storage'))) {
            $this->call('storage:link');
        }

        $seeder->run();
        $this->info(count(DevelopmentArticleSeeder::ARTICLE_SLUGS).' demo-artikelen zijn aangemaakt of bijgewerkt.');

        return self::SUCCESS;
    }
}
