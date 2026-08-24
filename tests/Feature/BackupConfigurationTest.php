<?php

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Str;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageInMegabytes;

test('it stores verified encrypted database backups under the existing s3 disk', function () {
    expect(config('backup.backup.name'))->toBe('backups/dds-platform')
        ->and(config('backup.backup.source.files.include'))->toBe([])
        ->and(config('backup.backup.source.databases'))->toBe([config('database.default')])
        ->and(config('backup.backup.destination.disks'))->toBe(['s3'])
        ->and(config('backup.backup.encryption'))->toBe('aes256')
        ->and(config('backup.backup.verify_backup'))->toBeTrue()
        ->and(config('filesystems.disks.backups'))->toBeNull();
});

test('it monitors backup freshness and storage use on the backup disk', function () {
    $monitoredBackup = config('backup.monitor_backups.0');

    expect($monitoredBackup['name'])->toBe('backups/dds-platform')
        ->and($monitoredBackup['disks'])->toBe(['s3'])
        ->and($monitoredBackup['health_checks'])->toHaveKeys([
            MaximumAgeInDays::class,
            MaximumStorageInMegabytes::class,
        ]);
});

test('it schedules production backup maintenance without overlap on one server', function () {
    $events = collect(app(Schedule::class)->events());

    assertBackupSchedule($events->first(
        fn (Event $event): bool => Str::contains($event->command ?? '', 'backup:run --only-db'),
    ), '30 1 * * *');
    assertBackupSchedule($events->first(
        fn (Event $event): bool => Str::contains($event->command ?? '', 'backup:monitor'),
    ), '0 3 * * *');
    assertBackupSchedule($events->first(
        fn (Event $event): bool => Str::contains($event->command ?? '', 'backup:clean'),
    ), '30 3 * * *');
});

function assertBackupSchedule(?Event $event, string $expression): void
{
    expect($event)->toBeInstanceOf(Event::class);

    assert($event instanceof Event);

    expect($event->expression)->toBe($expression)
        ->and($event->timezone)->toBe('UTC')
        ->and($event->environments)->toBe(['production'])
        ->and($event->onOneServer)->toBeTrue()
        ->and($event->withoutOverlapping)->toBeTrue();
}
