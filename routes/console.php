<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('backup:run-encrypted', function (): int {
    if (blank(config('backup.backup.password'))) {
        $this->error('BACKUP_ARCHIVE_PASSWORD must be configured before database backups can run.');

        return 1;
    }

    return $this->call('backup:run', ['--only-db' => true]);
})->purpose('Create an encrypted database backup');

Schedule::command('backup:run-encrypted')
    ->dailyAt('01:30')
    ->timezone('UTC')
    ->environments(['production'])
    ->onOneServer()
    ->withoutOverlapping(180);

Schedule::command('backup:monitor')
    ->dailyAt('03:00')
    ->timezone('UTC')
    ->environments(['production'])
    ->onOneServer()
    ->withoutOverlapping(60);

Schedule::command('backup:clean')
    ->dailyAt('03:30')
    ->timezone('UTC')
    ->environments(['production'])
    ->onOneServer()
    ->withoutOverlapping(180);
