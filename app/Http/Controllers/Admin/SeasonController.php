<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SeasonTicketSalesState;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSeasonRequest;
use App\Http\Requests\Admin\UpdateSeasonRequest;
use App\Models\Event;
use App\Models\Season;
use App\Models\SeasonTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class SeasonController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Season::class);

        return Inertia::render('admin/seasons/index', [
            'seasons' => fn () => $this->seasons(),
            'summary' => fn (): array => [
                'total' => Season::query()->count(),
                'withTickets' => SeasonTicket::query()
                    ->where('sales_state', '!=', SeasonTicketSalesState::NotOffered)
                    ->count(),
                'events' => Event::query()->whereNotNull('season_id')->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Season::class);

        return Inertia::render('admin/seasons/create', [
            'salesStateOptions' => $this->salesStateOptions(),
        ]);
    }

    public function store(StoreSeasonRequest $request): RedirectResponse
    {
        $season = DB::transaction(function () use ($request): Season {
            $season = Season::query()->create($request->seasonData());
            $this->syncTicket($season, $request->ticketData());

            return $season;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Seizoen aangemaakt.']);

        return to_route('admin.seasons.edit', $season);
    }

    public function edit(Season $season): Response
    {
        Gate::authorize('update', $season);

        $season->load('seasonTicket');

        return Inertia::render('admin/seasons/edit', [
            'season' => $this->formSeason($season),
            'salesStateOptions' => $this->salesStateOptions(),
        ]);
    }

    public function update(UpdateSeasonRequest $request, Season $season): RedirectResponse
    {
        DB::transaction(function () use ($request, $season): void {
            $season->update($request->seasonData());
            $this->syncTicket($season, $request->ticketData());
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Seizoen opgeslagen.']);

        return to_route('admin.seasons.edit', $season);
    }

    public function destroy(Season $season): RedirectResponse
    {
        Gate::authorize('delete', $season);

        if ($season->events()->exists()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Verplaats of verwijder eerst de gekoppelde events.',
            ]);

            return back();
        }

        $season->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Seizoen verwijderd.']);

        return to_route('admin.seasons.index');
    }

    /** @param array<string, mixed>|null $ticketData */
    private function syncTicket(Season $season, ?array $ticketData): void
    {
        if ($ticketData === null) {
            $season->seasonTicket()->delete();

            return;
        }

        $season->seasonTicket()->updateOrCreate([], $ticketData);
    }

    /**
     * @return LengthAwarePaginator<int, covariant array{
     *     id: int,
     *     name: string,
     *     slug: string,
     *     eventCount: int,
     *     updatedAt: string,
     *     ticket: array{salesState: string, priceCents: int|null, capacity: int|null}|null
     * }>
     */
    private function seasons(): LengthAwarePaginator
    {
        return Season::query()
            ->select(['id', 'name', 'slug', 'updated_at'])
            ->withCount('events')
            ->with('seasonTicket:id,season_id,sales_state,price_cents,capacity')
            ->latest('id')
            ->paginate(25)
            ->through(fn (Season $season): array => [
                'id' => $season->id,
                'name' => $season->name,
                'slug' => $season->slug,
                'eventCount' => $season->events_count,
                'updatedAt' => $season->updated_at->toIso8601String(),
                'ticket' => $season->seasonTicket === null
                    || $season->seasonTicket->sales_state === SeasonTicketSalesState::NotOffered ? null : [
                        'salesState' => $season->seasonTicket->sales_state->value,
                        'priceCents' => $season->seasonTicket->price_cents,
                        'capacity' => $season->seasonTicket->capacity,
                    ],
            ]);
    }

    /** @return array<string, mixed> */
    private function formSeason(Season $season): array
    {
        $ticket = $season->seasonTicket;
        $ticketOffered = $ticket !== null
            && $ticket->sales_state !== SeasonTicketSalesState::NotOffered;

        return [
            'id' => $season->id,
            'name' => $season->name,
            'slug' => $season->slug,
            'eventCount' => $season->events()->count(),
            'ticketOffered' => $ticketOffered,
            'ticketSalesState' => $ticketOffered
                ? $ticket->sales_state->value
                : SeasonTicketSalesState::ComingSoon->value,
            'ticketPriceEuros' => $ticket?->price_cents === null
                ? null
                : number_format($ticket->price_cents / 100, 2, '.', ''),
            'ticketCapacity' => $ticket?->capacity,
            'ticketSalesOpensAt' => $ticket?->sales_opens_at?->format('Y-m-d\TH:i'),
            'ticketSalesClosesAt' => $ticket?->sales_closes_at?->format('Y-m-d\TH:i'),
            'ticketRegistrationUrl' => $ticket?->registration_url,
            'ticketCopy' => $ticket?->copy,
        ];
    }

    /** @return list<array{value: string, label: string}> */
    private function salesStateOptions(): array
    {
        return [
            ['value' => SeasonTicketSalesState::ComingSoon->value, 'label' => 'Binnenkort'],
            ['value' => SeasonTicketSalesState::Available->value, 'label' => 'Beschikbaar'],
            ['value' => SeasonTicketSalesState::SoldOut->value, 'label' => 'Uitverkocht'],
            ['value' => SeasonTicketSalesState::Closed->value, 'label' => 'Gesloten'],
        ];
    }
}
