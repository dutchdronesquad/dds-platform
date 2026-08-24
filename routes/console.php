<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('backup:run --only-db')
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
