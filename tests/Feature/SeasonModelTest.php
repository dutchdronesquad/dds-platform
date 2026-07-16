<?php

use App\Models\Event;
use App\Models\Season;
use App\Models\SeasonTicket;
use Illuminate\Database\QueryException;

test('a season can group events without offering a season ticket', function () {
    $season = Season::factory()->create(['name' => 'Winter training series']);
    Event::factory()->count(3)->for($season)->create();

    $season->load(['events', 'seasonTicket']);

    expect($season)
        ->name->toBe('Winter training series')
        ->events->toHaveCount(3)
        ->seasonTicket->toBeNull();
});

test('a season can have one optional ticket product', function () {
    $season = Season::factory()->withTicketOffer()->create()->load('seasonTicket');

    expect($season->seasonTicket)
        ->toBeInstanceOf(SeasonTicket::class)
        ->and($season->seasonTicket?->season->is($season))->toBeTrue();
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
