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
 * Cancelled events remain in the season range and cancelled eligible events remain in the
 * ticket count so presentation can mark them in context. No refund or replacement rule is inferred.
 */
final readonly class SeasonTicketSummary
{
    private function __construct(
        public SeasonTicketSalesState $salesState,
        public ?CarbonImmutable $startsAt,
        public ?CarbonImmutable $endsAt,
        public int $eligibleEventCount,
        public int $cancelledEligibleEventCount,
    ) {}

    public static function fromSeason(Season $season, ?CarbonInterface $at = null): self
    {
        $season->loadMissing(['events', 'seasonTicket.eligibleEvents']);
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
                eligibleEventCount: 0,
                cancelledEligibleEventCount: 0,
            );
        }

        $cancelledEligibleEventCount = $seasonTicket->eligibleEvents
            ->filter(
                static fn (Event $event): bool => $event->status === EventStatus::Cancelled,
            )
            ->count();

        return new self(
            salesState: $seasonTicket->currentSalesState($at),
            startsAt: $startsAt,
            endsAt: $endsAt,
            eligibleEventCount: $seasonTicket->eligibleEvents->count(),
            cancelledEligibleEventCount: $cancelledEligibleEventCount,
        );
    }
}
