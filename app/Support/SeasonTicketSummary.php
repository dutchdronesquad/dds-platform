<?php

namespace App\Support;

use App\Enums\EventStatus;
use App\Enums\SeasonTicketSalesState;
use App\Models\Event;
use App\Models\Season;
use App\Models\SeasonTicket;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Public season-ticket projection.
 *
 * Season dates come from every grouped event, independently of whether a ticket is offered.
 * When a ticket exists, every event in the season is covered by it.
 */
final readonly class SeasonTicketSummary
{
    private function __construct(
        public SeasonTicketSalesState $salesState,
        public ?CarbonImmutable $startsAt,
        public ?CarbonImmutable $endsAt,
        public int $eventCount,
        public int $cancelledEventCount,
    ) {}

    public static function fromSeason(Season $season, ?CarbonInterface $at = null): self
    {
        $season->loadMissing(['events', 'seasonTicket']);
        $seasonTicket = $season->seasonTicket;

        $startsAt = null;
        $endsAt = null;

        foreach ($season->events as $event) {
            if ($startsAt === null || $event->starts_at->lessThan($startsAt)) {
                $startsAt = $event->starts_at;
            }

            $eventEndsAt = $event->ends_at ?? $event->starts_at;

            if ($endsAt === null || $eventEndsAt->greaterThan($endsAt)) {
                $endsAt = $eventEndsAt;
            }
        }

        if (! $seasonTicket instanceof SeasonTicket) {
            return new self(
                salesState: SeasonTicketSalesState::NotOffered,
                startsAt: $startsAt,
                endsAt: $endsAt,
                eventCount: 0,
                cancelledEventCount: 0,
            );
        }

        $cancelledEventCount = $season->events
            ->filter(
                static fn (Event $event): bool => $event->status === EventStatus::Cancelled,
            )
            ->count();

        return new self(
            salesState: $seasonTicket->currentSalesState($at),
            startsAt: $startsAt,
            endsAt: $endsAt,
            eventCount: $season->events->count(),
            cancelledEventCount: $cancelledEventCount,
        );
    }
}
