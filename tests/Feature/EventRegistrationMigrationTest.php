<?php

use App\Models\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('existing registration states are migrated without unexpectedly opening closed events', function () {
    $this->travelTo('2026-08-28 12:00:00');

    $openEvent = Event::factory()->create();
    $fullEvent = Event::factory()->create();
    $waitlistEvent = Event::factory()->create();
    $scheduledEvent = Event::factory()->create([
        'registration_opens_at' => '2026-09-15 10:00:00',
        'registration_url' => 'https://example.com/scheduled',
    ]);
    $closedEvent = Event::factory()->create([
        'registration_opens_at' => '2026-09-15 10:00:00',
    ]);
    $previouslyClosedEvent = Event::factory()->create([
        'registration_opens_at' => '2026-08-01 10:00:00',
        'registration_url' => 'https://example.com/closed',
    ]);

    DB::table('events')->where('id', $openEvent->id)->update(['registration_status' => 'open']);
    DB::table('events')->where('id', $fullEvent->id)->update(['registration_status' => 'full']);
    DB::table('events')->where('id', $waitlistEvent->id)->update(['registration_status' => 'waitlist']);

    $migration = require database_path('migrations/2026_08_28_194534_replace_event_registration_status_with_automatic_state.php');
    $migrateDown = [$migration, 'down'];
    $migrateUp = [$migration, 'up'];

    if (! is_callable($migrateDown) || ! is_callable($migrateUp)) {
        throw new RuntimeException('The event registration migration must be reversible.');
    }

    $migrateDown();
    $migrateUp();

    $states = DB::table('events')
        ->whereIn('id', [
            $openEvent->id,
            $fullEvent->id,
            $waitlistEvent->id,
            $scheduledEvent->id,
            $closedEvent->id,
            $previouslyClosedEvent->id,
        ])
        ->get()
        ->keyBy('id');

    expect(Schema::hasColumn('events', 'registration_status'))->toBeTrue()
        ->and($states[$openEvent->id]->registration_enabled)->toBeTruthy()
        ->and($states[$fullEvent->id]->registration_enabled)->toBeTruthy()
        ->and($states[$fullEvent->id]->registration_full)->toBeTruthy()
        ->and($states[$waitlistEvent->id]->registration_enabled)->toBeTruthy()
        ->and($states[$waitlistEvent->id]->registration_full)->toBeTruthy()
        ->and($states[$waitlistEvent->id]->registration_waitlist_enabled)->toBeTruthy()
        ->and($states[$scheduledEvent->id]->registration_enabled)->toBeTruthy()
        ->and($states[$closedEvent->id]->registration_enabled)->toBeFalsy()
        ->and($states[$previouslyClosedEvent->id]->registration_enabled)->toBeFalsy();
});
