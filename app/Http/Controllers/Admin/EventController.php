<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EventRegistrationStatus;
use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEventRequest;
use App\Http\Requests\Admin\UpdateEventRequest;
use App\Models\Event;
use App\Models\Location;
use App\Models\Season;
use App\Models\User;
use App\Support\MediaAssetPickerData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

final class EventController extends Controller
{
    private const string SITUATION_CLOSED_REGISTRATION = 'closed_registration';

    private const string SITUATION_EXPIRED_REGISTRATION = 'expired_registration';

    private const string SITUATION_WITHOUT_CONTENT = 'without_content';

    private const string SITUATION_WITHOUT_COVER = 'without_cover';

    private const string SITUATION_WITHOUT_SEASON = 'without_season';

    public function __construct(private MediaAssetPickerData $mediaAssetPickerData) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Event::class);

        $filters = $this->filters($request);

        return Inertia::render('admin/events/index', [
            'events' => fn () => $this->events($request->user(), $filters),
            'filters' => $filters,
            'summary' => fn (): array => $this->summary(),
            'canCreate' => $request->user()->can('create', Event::class),
            'canManageSeasons' => $request->user()->can('viewAny', Season::class),
            'situationOptions' => $this->situationOptions(),
            'statusOptions' => $this->statusOptions(),
            'typeOptions' => $this->typeOptions(),
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', Event::class);

        return Inertia::render('admin/events/create', [
            'options' => $this->formOptions(),
            'canManageSeasons' => $request->user()->can('viewAny', Season::class),
        ]);
    }

    public function store(StoreEventRequest $request): RedirectResponse
    {
        $event = Event::query()->create($request->eventData());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Event aangemaakt als concept.']);

        return to_route('admin.events.edit', $event);
    }

    public function edit(Request $request, Event $event): Response
    {
        Gate::authorize('update', $event);

        $event->load(['coverImage', 'createdBy:id,name', 'updatedBy:id,name']);

        return Inertia::render('admin/events/edit', [
            'event' => $this->formEvent($request->user(), $event),
            'options' => $this->formOptions(),
            'canManageSeasons' => $request->user()->can('viewAny', Season::class),
        ]);
    }

    public function update(UpdateEventRequest $request, Event $event): RedirectResponse
    {
        $event->update($request->eventData());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Event opgeslagen.']);

        return to_route('admin.events.edit', $event);
    }

    public function destroy(Event $event): RedirectResponse
    {
        Gate::authorize('delete', $event);

        $event->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Event verwijderd.']);

        return to_route('admin.events.index');
    }

    /** @return array{search: string, situation: list<string>, status: list<string>, type: list<string>} */
    private function filters(Request $request): array
    {
        $requestedSituations = $request->array('situation');
        $requestedStatuses = $request->array('status');
        $requestedTypes = $request->array('type');

        $situations = array_values(array_filter(
            [
                self::SITUATION_CLOSED_REGISTRATION,
                self::SITUATION_EXPIRED_REGISTRATION,
                self::SITUATION_WITHOUT_CONTENT,
                self::SITUATION_WITHOUT_COVER,
                self::SITUATION_WITHOUT_SEASON,
            ],
            fn (string $value): bool => in_array($value, $requestedSituations, true),
        ));

        $statuses = array_values(array_map(
            fn (EventStatus $status): string => $status->value,
            array_filter(
                EventStatus::cases(),
                fn (EventStatus $status): bool => in_array($status->value, $requestedStatuses, true),
            ),
        ));
        $types = array_values(array_map(
            fn (EventType $type): string => $type->value,
            array_filter(
                EventType::cases(),
                fn (EventType $type): bool => in_array($type->value, $requestedTypes, true),
            ),
        ));

        return [
            'search' => Str::substr($request->string('search')->trim()->toString(), 0, 100),
            'situation' => $situations,
            'status' => $statuses,
            'type' => $types,
        ];
    }

    /**
     * @param  array{search: string, situation: list<string>, status: list<string>, type: list<string>}  $filters
     * @return LengthAwarePaginator<int, covariant array{
     *     id: int,
     *     title: string,
     *     slug: string,
     *     startsAt: string,
     *     activity: array{updatedAt: string, updatedBy: array{id: int, name: string}|null},
     *     publishedAt: string|null,
     *     status: string,
     *     type: string,
     *     registrationStatus: string,
     *     location: array{name: string, city: string},
     *     season: array{name: string, slug: string}|null,
     *     capabilities: array{update: bool, delete: bool, publish: bool, cancel: bool}
     * }>
     */
    private function events(User $user, array $filters): LengthAwarePaginator
    {
        $query = Event::query()
            ->select([
                'id',
                'location_id',
                'season_id',
                'title',
                'slug',
                'starts_at',
                'published_at',
                'status',
                'type',
                'registration_status',
                'updated_by',
                'updated_at',
            ])
            ->with([
                'location:id,name,city',
                'season:id,name,slug',
                'updatedBy:id,name',
            ]);

        $this->applySearch($query, $filters['search']);
        $this->applySituationFilters($query, $filters['situation']);

        return $query
            ->when($filters['status'] !== [], fn (Builder $query): Builder => $query
                ->whereIn('status', $filters['status']))
            ->when($filters['type'] !== [], fn (Builder $query): Builder => $query
                ->whereIn('type', $filters['type']))
            ->latest('starts_at')
            ->latest('id')
            ->paginate(25)
            ->appends(array_filter([
                'search' => $filters['search'] !== '' ? $filters['search'] : null,
                'situation' => $filters['situation'] !== [] ? $filters['situation'] : null,
                'status' => $filters['status'] !== [] ? $filters['status'] : null,
                'type' => $filters['type'] !== [] ? $filters['type'] : null,
            ], fn (mixed $value): bool => $value !== null))
            ->through(fn (Event $event): array => [
                'id' => $event->id,
                'title' => $event->title,
                'slug' => $event->slug,
                'startsAt' => $event->starts_at->toIso8601String(),
                'activity' => [
                    'updatedAt' => $event->updated_at->toIso8601String(),
                    'updatedBy' => $event->updatedBy === null ? null : [
                        'id' => $event->updatedBy->id,
                        'name' => $event->updatedBy->name,
                    ],
                ],
                'publishedAt' => $event->published_at?->toIso8601String(),
                'status' => $event->status->value,
                'type' => $event->type->value,
                'registrationStatus' => $event->registration_status->value,
                'location' => [
                    'name' => $event->location->name,
                    'city' => $event->location->city,
                ],
                'season' => $event->season === null ? null : [
                    'name' => $event->season->name,
                    'slug' => $event->season->slug,
                ],
                'capabilities' => [
                    'update' => $user->can('update', $event),
                    'delete' => $user->can('delete', $event),
                    'publish' => $user->can('publish', $event),
                    'cancel' => $user->can('cancel', $event),
                ],
            ]);
    }

    /**
     * @param  Builder<Event>  $query
     * @param  list<string>  $situations
     */
    private function applySituationFilters(Builder $query, array $situations): void
    {
        if ($situations === []) {
            return;
        }

        $referenceTime = now();

        $query->where(function (Builder $query) use ($referenceTime, $situations): void {
            if (in_array(self::SITUATION_CLOSED_REGISTRATION, $situations, true)) {
                $query->orWhere(function (Builder $query) use ($referenceTime): void {
                    $query
                        ->where('starts_at', '>=', $referenceTime)
                        ->where('status', EventStatus::Published->value)
                        ->where('registration_status', EventRegistrationStatus::Closed->value);
                });
            }

            if (in_array(self::SITUATION_EXPIRED_REGISTRATION, $situations, true)) {
                $query->orWhere(function (Builder $query) use ($referenceTime): void {
                    $query
                        ->where('starts_at', '>=', $referenceTime)
                        ->where('status', '!=', EventStatus::Cancelled->value)
                        ->where('registration_status', EventRegistrationStatus::Open->value)
                        ->where('registration_deadline_at', '<', $referenceTime);
                });
            }

            if (in_array(self::SITUATION_WITHOUT_CONTENT, $situations, true)) {
                $query->orWhere(function (Builder $query) use ($referenceTime): void {
                    $query
                        ->where('starts_at', '>=', $referenceTime)
                        ->where('status', '!=', EventStatus::Cancelled->value)
                        ->where(function (Builder $query): void {
                            $query
                                ->whereNull('content')
                                ->orWhere('content', '');
                        });
                });
            }

            if (in_array(self::SITUATION_WITHOUT_COVER, $situations, true)) {
                $query->orWhere(function (Builder $query) use ($referenceTime): void {
                    $query
                        ->where('starts_at', '>=', $referenceTime)
                        ->where('status', '!=', EventStatus::Cancelled->value)
                        ->whereNull('cover_image_id');
                });
            }

            if (in_array(self::SITUATION_WITHOUT_SEASON, $situations, true)) {
                $query->orWhere(function (Builder $query) use ($referenceTime): void {
                    $query
                        ->where('starts_at', '>=', $referenceTime)
                        ->where('status', '!=', EventStatus::Cancelled->value)
                        ->whereNull('season_id');
                });
            }
        });
    }

    /** @param Builder<Event> $query */
    private function applySearch(Builder $query, string $search): void
    {
        if ($search === '') {
            return;
        }

        $searchPattern = '%'.Str::lower($search).'%';

        $query->where(function (Builder $query) use ($searchPattern): void {
            $query
                ->whereRaw('LOWER(title) LIKE ?', [$searchPattern])
                ->orWhereRaw('LOWER(slug) LIKE ?', [$searchPattern])
                ->orWhereHas('location', fn (Builder $query): Builder => $query
                    ->whereRaw('LOWER(name) LIKE ?', [$searchPattern]))
                ->orWhereHas('season', fn (Builder $query): Builder => $query
                    ->whereRaw('LOWER(name) LIKE ?', [$searchPattern]));
        });
    }

    /** @return array{total: int, drafts: int, published: int, cancelled: int} */
    private function summary(): array
    {
        $summary = Event::query()
            ->toBase()
            ->selectRaw('count(*) as total')
            ->selectRaw('count(case when status = ? then 1 end) as drafts', [EventStatus::Draft->value])
            ->selectRaw('count(case when status = ? then 1 end) as published', [EventStatus::Published->value])
            ->selectRaw('count(case when status = ? then 1 end) as cancelled', [EventStatus::Cancelled->value])
            ->first();

        return [
            'total' => (int) $summary->total,
            'drafts' => (int) $summary->drafts,
            'published' => (int) $summary->published,
            'cancelled' => (int) $summary->cancelled,
        ];
    }

    /** @return array<string, mixed> */
    private function formOptions(): array
    {
        return [
            'locations' => Location::query()
                ->select(['id', 'name', 'city'])
                ->orderBy('name')
                ->get()
                ->map(fn (Location $location): array => [
                    'id' => $location->id,
                    'label' => "{$location->name} — {$location->city}",
                ]),
            'seasons' => Season::query()
                ->select(['id', 'name'])
                ->orderByDesc('id')
                ->get()
                ->map(fn (Season $season): array => [
                    'id' => $season->id,
                    'label' => $season->name,
                ]),
            'types' => $this->typeOptions(),
            'registrationStatuses' => $this->registrationStatusOptions(),
        ];
    }

    /** @return array<string, mixed> */
    private function formEvent(User $user, Event $event): array
    {
        return [
            'id' => $event->id,
            'title' => $event->title,
            'slug' => $event->slug,
            'content' => $event->content,
            'locationId' => $event->location_id,
            'seasonId' => $event->season_id,
            'coverImageId' => $event->cover_image_id,
            'coverImage' => $event->coverImage === null
                ? null
                : $this->mediaAssetPickerData->one($event->coverImage),
            'startsAt' => $event->starts_at->format('Y-m-d\TH:i'),
            'endsAt' => $event->ends_at?->format('Y-m-d\TH:i'),
            'status' => $event->status->value,
            'type' => $event->type->value,
            'priceEuros' => $event->price_cents === null
                ? null
                : number_format($event->price_cents / 100, 2, '.', ''),
            'capacity' => $event->capacity,
            'registrationOpensAt' => $event->registration_opens_at?->format('Y-m-d\TH:i'),
            'registrationDeadlineAt' => $event->registration_deadline_at?->format('Y-m-d\TH:i'),
            'registrationStatus' => $event->registration_status->value,
            'registrationUrl' => $event->registration_url,
            'publishedAt' => $event->published_at?->toIso8601String(),
            'activity' => [
                'createdAt' => $event->created_at->toIso8601String(),
                'createdBy' => $event->createdBy === null ? null : [
                    'id' => $event->createdBy->id,
                    'name' => $event->createdBy->name,
                ],
                'updatedAt' => $event->updated_at->toIso8601String(),
                'updatedBy' => $event->updatedBy === null ? null : [
                    'id' => $event->updatedBy->id,
                    'name' => $event->updatedBy->name,
                ],
            ],
            'capabilities' => [
                'delete' => $user->can('delete', $event),
                'publish' => $user->can('publish', $event),
                'cancel' => $user->can('cancel', $event),
            ],
        ];
    }

    /** @return list<array{value: string, label: string}> */
    private function statusOptions(): array
    {
        return [
            ['value' => EventStatus::Draft->value, 'label' => 'Concept'],
            ['value' => EventStatus::Published->value, 'label' => 'Gepubliceerd'],
            ['value' => EventStatus::Cancelled->value, 'label' => 'Geannuleerd'],
        ];
    }

    /** @return list<array{value: string, label: string}> */
    private function situationOptions(): array
    {
        return [
            ['value' => self::SITUATION_CLOSED_REGISTRATION, 'label' => 'Registratie gesloten'],
            ['value' => self::SITUATION_EXPIRED_REGISTRATION, 'label' => 'Inschrijfdeadline verlopen'],
            ['value' => self::SITUATION_WITHOUT_CONTENT, 'label' => 'Zonder inhoud'],
            ['value' => self::SITUATION_WITHOUT_COVER, 'label' => 'Zonder omslagafbeelding'],
            ['value' => self::SITUATION_WITHOUT_SEASON, 'label' => 'Zonder seizoen'],
        ];
    }

    /** @return list<array{value: string, label: string}> */
    private function typeOptions(): array
    {
        return [
            ['value' => EventType::Training->value, 'label' => 'Training'],
            ['value' => EventType::Race->value, 'label' => 'Race'],
            ['value' => EventType::Demo->value, 'label' => 'Demo'],
            ['value' => EventType::Workshop->value, 'label' => 'Workshop'],
            ['value' => EventType::Other->value, 'label' => 'Overig'],
        ];
    }

    /** @return list<array{value: string, label: string}> */
    private function registrationStatusOptions(): array
    {
        return [
            ['value' => EventRegistrationStatus::Closed->value, 'label' => 'Gesloten'],
            ['value' => EventRegistrationStatus::Open->value, 'label' => 'Open'],
            ['value' => EventRegistrationStatus::Waitlist->value, 'label' => 'Wachtlijst'],
            ['value' => EventRegistrationStatus::Full->value, 'label' => 'Vol'],
        ];
    }
}
