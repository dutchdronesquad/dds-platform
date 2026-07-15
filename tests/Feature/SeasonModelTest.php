<?php

use App\Models\Event;
use App\Models\Season;
use Illuminate\Database\QueryException;

test('seasons can define an optional price and ticket capacity', function () {
    $season = Season::factory()->create([
        'price_cents' => 9_000,
        'ticket_capacity' => 10,
    ]);

    $this->assertModelExists($season);

    expect($season)
        ->name->toBeString()
        ->price_cents->toBe(9_000)
        ->ticket_capacity->toBe(10);
});

test('a season can omit its price and ticket capacity', function () {
    $season = Season::factory()->create([
        'price_cents' => null,
        'ticket_capacity' => null,
    ]);

    expect($season->price_cents)->toBeNull()
        ->and($season->ticket_capacity)->toBeNull();
});

test('seasons contain their linked events', function () {
    $season = Season::factory()->create();
    $event = Event::factory()->for($season)->create();

    expect($season->events)->toHaveCount(1)
        ->and($season->events->first()->is($event))->toBeTrue();
});

test('seasons referenced by events cannot be deleted', function () {
    $event = Event::factory()->inSeason()->create()->load('season');

    expect(fn () => $event->season->delete())
        ->toThrow(QueryException::class);
});
