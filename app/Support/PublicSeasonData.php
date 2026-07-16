<?php

namespace App\Support;

use App\Enums\EventType;
use App\Enums\SeasonTicketSalesState;
use App\Models\Season;
use App\Models\SeasonTicket;

final class PublicSeasonData
{
    /**
     * @return array{
     *     id: int,
     *     slug: string,
     *     name: string,
     *     startsAt: string|null,
     *     endsAt: string|null,
     *     eventCount: int,
     *     ticket: array{
     *         salesState: string,
     *         salesOpensAt: string|null,
     *         salesClosesAt: string|null,
     *         registrationUrl: string|null,
     *         copy: string|null,
     *         priceCents: int|null,
     *         capacity: int|null,
     *     }|null,
     * }
     */
    public function summary(
        Season $season,
        ?EventType $eventType = null,
    ): array {
        $eventsQuery = $season->events()
            ->select([
                'id',
                'season_id',
                'starts_at',
                'ends_at',
                'status',
                'type',
            ])
            ->publiclyVisible()
            ->oldest('starts_at')
            ->oldest('id');

        if ($eventType !== null) {
            $eventsQuery->where('type', $eventType);
        }

        $events = $eventsQuery->get();
        $startsAt = null;
        $endsAt = null;

        foreach ($events as $event) {
            if ($startsAt === null || $event->starts_at->lessThan($startsAt)) {
                $startsAt = $event->starts_at;
            }

            $eventEndsAt = $event->ends_at ?? $event->starts_at;

            if ($endsAt === null || $eventEndsAt->greaterThan($endsAt)) {
                $endsAt = $eventEndsAt;
            }
        }

        return [
            'id' => $season->id,
            'slug' => $season->slug,
            'name' => $season->name,
            'startsAt' => $startsAt?->toIso8601String(),
            'endsAt' => $endsAt?->toIso8601String(),
            'eventCount' => $events->count(),
            'ticket' => $this->ticket($season),
        ];
    }

    /**
     * @return array{
     *     salesState: string,
     *     salesOpensAt: string|null,
     *     salesClosesAt: string|null,
     *     registrationUrl: string|null,
     *     copy: string|null,
     *     priceCents: int|null,
     *     capacity: int|null,
     * }|null
     */
    private function ticket(Season $season): ?array
    {
        $seasonTicket = $season->seasonTicket()->first();

        if (! $seasonTicket instanceof SeasonTicket) {
            return null;
        }

        $salesState = $seasonTicket->currentSalesState();

        if ($salesState === SeasonTicketSalesState::NotOffered) {
            return null;
        }

        return [
            'salesState' => $salesState->value,
            'salesOpensAt' => $seasonTicket->sales_opens_at?->toIso8601String(),
            'salesClosesAt' => $seasonTicket->sales_closes_at?->toIso8601String(),
            'registrationUrl' => $salesState === SeasonTicketSalesState::Available
                ? $seasonTicket->registration_url
                : null,
            'copy' => $seasonTicket->copy,
            'priceCents' => $seasonTicket->price_cents,
            'capacity' => $seasonTicket->capacity,
        ];
    }
}
