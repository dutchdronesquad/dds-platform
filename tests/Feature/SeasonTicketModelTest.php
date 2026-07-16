<?php

use App\Enums\EventStatus;
use App\Enums\SeasonTicketSalesState;
use App\Models\Event;
use App\Models\Season;
use App\Models\SeasonTicket;
use App\Support\SeasonTicketSummary;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    CarbonImmutable::setTestNow('2026-07-16 12:00:00');
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

test('season tickets expose casts and only explicitly eligible events', function () {
    $season = Season::factory()->create();
    $eligibleEvent = Event::factory()->for($season)->create(['capacity' => 16]);
    $excludedEvent = Event::factory()->for($season)->create();
    $seasonTicket = SeasonTicket::query()->create([
        'season_id' => $season->id,
        'sales_state' => SeasonTicketSalesState::Available->value,
        'sales_opens_at' => '2026-07-01 10:00:00',
        'sales_closes_at' => '2026-08-01 10:00:00',
        'registration_url' => 'https://example.com/season-ticket',
        'copy' => 'Covers the selected competition rounds.',
        'price_cents' => '9000',
        'capacity' => '10',
    ]);

    $seasonTicket->eligibleEvents()->attach($eligibleEvent);
    $seasonTicket->refresh()->load(['season', 'eligibleEvents']);
    $eligibleEvent->load('seasonTickets');

    $this->assertModelExists($seasonTicket);

    expect($seasonTicket)
        ->sales_state->toBe(SeasonTicketSalesState::Available)
        ->sales_opens_at->toBeInstanceOf(CarbonImmutable::class)
        ->sales_closes_at->toBeInstanceOf(CarbonImmutable::class)
        ->price_cents->toBe(9_000)
        ->capacity->toBe(10)
        ->and($seasonTicket->season->is($season))->toBeTrue()
        ->and($seasonTicket->eligibleEvents)->toHaveCount(1)
        ->and($seasonTicket->eligibleEvents->first()?->is($eligibleEvent))->toBeTrue()
        ->and($seasonTicket->eligibleEvents->contains($excludedEvent))->toBeFalse()
        ->and($eligibleEvent->seasonTickets->first()?->is($seasonTicket))->toBeTrue()
        ->and($eligibleEvent->capacity)->toBe(16);
});

test('season ticket sales state values are enforced by the database', function () {
    $seasonTicket = SeasonTicket::factory()->create();

    expect(fn () => DB::table($seasonTicket->getTable())
        ->where('id', $seasonTicket->id)
        ->update(['sales_state' => 'unsupported']))
        ->toThrow(QueryException::class);
});

test('season ticket data is stored separately from generic season grouping', function () {
    expect(Schema::hasColumn('seasons', 'price_cents'))->toBeFalse()
        ->and(Schema::hasColumn('seasons', 'ticket_capacity'))->toBeFalse()
        ->and(Schema::hasColumns('season_tickets', ['price_cents', 'capacity']))->toBeTrue();
});

test('a season can have at most one ticket product', function () {
    $season = Season::factory()->create();
    SeasonTicket::factory()->for($season)->create();

    expect(fn () => SeasonTicket::factory()->for($season)->create())
        ->toThrow(QueryException::class);
});

test('factory states represent every configured sales state', function (
    string $factoryState,
    SeasonTicketSalesState $expectedState,
) {
    $seasonTicket = SeasonTicket::factory()->{$factoryState}()->create();

    expect($seasonTicket->sales_state)->toBe($expectedState)
        ->and($seasonTicket->currentSalesState())->toBe($expectedState);
})->with([
    'not offered' => ['notOffered', SeasonTicketSalesState::NotOffered],
    'coming soon' => ['comingSoon', SeasonTicketSalesState::ComingSoon],
    'available' => ['available', SeasonTicketSalesState::Available],
    'sold out' => ['soldOut', SeasonTicketSalesState::SoldOut],
    'closed' => ['closed', SeasonTicketSalesState::Closed],
]);

test('sales windows produce one reliable current state', function (
    SeasonTicketSalesState $configuredState,
    ?string $opensAt,
    ?string $closesAt,
    SeasonTicketSalesState $expectedState,
) {
    $seasonTicket = SeasonTicket::factory()->create([
        'sales_state' => $configuredState,
        'sales_opens_at' => $opensAt,
        'sales_closes_at' => $closesAt,
    ]);

    expect($seasonTicket->currentSalesState())->toBe($expectedState);
})->with([
    'available but not open yet' => [
        SeasonTicketSalesState::Available,
        '2026-07-17 12:00:00',
        '2026-08-17 12:00:00',
        SeasonTicketSalesState::ComingSoon,
    ],
    'available but window has closed' => [
        SeasonTicketSalesState::Available,
        '2026-06-01 12:00:00',
        '2026-07-16 12:00:00',
        SeasonTicketSalesState::Closed,
    ],
    'coming soon window has opened' => [
        SeasonTicketSalesState::ComingSoon,
        '2026-07-01 12:00:00',
        '2026-08-01 12:00:00',
        SeasonTicketSalesState::Available,
    ],
    'sold out during active window' => [
        SeasonTicketSalesState::SoldOut,
        '2026-07-01 12:00:00',
        '2026-08-01 12:00:00',
        SeasonTicketSalesState::SoldOut,
    ],
]);

test('a season summary separates its grouped date range from ticket eligibility', function () {
    $season = Season::factory()->create();
    $firstEligibleEvent = Event::factory()->for($season)->create([
        'starts_at' => '2026-09-10 18:00:00',
        'ends_at' => '2026-09-10 21:00:00',
    ]);
    $cancelledEligibleEvent = Event::factory()->cancelled()->for($season)->create([
        'starts_at' => '2027-01-15 09:00:00',
        'ends_at' => null,
        'status' => EventStatus::Cancelled,
    ]);
    Event::factory()->for($season)->create([
        'starts_at' => '2027-05-20 09:00:00',
        'ends_at' => '2027-05-20 17:00:00',
    ]);
    $seasonTicket = SeasonTicket::factory()->available()->for($season)->create();
    $seasonTicket->eligibleEvents()->attach([$firstEligibleEvent->id, $cancelledEligibleEvent->id]);

    $summary = SeasonTicketSummary::fromSeason($season);

    expect($summary)
        ->salesState->toBe(SeasonTicketSalesState::Available)
        ->eligibleEventCount->toBe(2)
        ->cancelledEligibleEventCount->toBe(1)
        ->and($summary->startsAt?->toDateTimeString())->toBe('2026-09-10 18:00:00')
        ->and($summary->endsAt?->toDateTimeString())->toBe('2027-05-20 17:00:00');
});

test('a season without a ticket still exposes its grouped event range', function () {
    $season = Season::factory()->create();
    Event::factory()->for($season)->create([
        'starts_at' => '2028-02-10 18:00:00',
        'ends_at' => '2028-02-10 21:00:00',
    ]);

    $summary = SeasonTicketSummary::fromSeason($season);

    expect($summary)
        ->salesState->toBe(SeasonTicketSalesState::NotOffered)
        ->eligibleEventCount->toBe(0)
        ->and($summary->startsAt?->toDateTimeString())->toBe('2028-02-10 18:00:00')
        ->and($summary->endsAt?->toDateTimeString())->toBe('2028-02-10 21:00:00');
});

test('the season ticket factory can create eligible events in the same season', function () {
    $seasonTicket = SeasonTicket::factory()->withEligibleEvents(2)->create()->load('eligibleEvents');

    expect($seasonTicket->eligibleEvents)->toHaveCount(2)
        ->and($seasonTicket->eligibleEvents->every(
            fn (Event $event): bool => $event->season_id === $seasonTicket->season_id,
        ))->toBeTrue();
});
