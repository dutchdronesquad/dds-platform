<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EventRegistrationStatus;
use App\Enums\EventStatus;
use App\Enums\Permission;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Season;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $isAdmin = $user->hasRole(Role::Admin->value);
        $canViewEvents = $user->can(Permission::ViewEvents->value);
        $canManageSeasons = $user->can('viewAny', Season::class);
        $referenceTime = now();
        $recentCutoff = $referenceTime->copy()->subDays(7);

        $eventSummary = $canViewEvents
            ? $this->eventSummary($referenceTime, $recentCutoff)
            : $this->emptyEventSummary();
        $seasonSummary = $canManageSeasons
            ? $this->seasonSummary($recentCutoff)
            : ['total' => 0, 'recent' => 0];

        return Inertia::render('dashboard', [
            'resources' => $this->resources($user, $isAdmin, $canViewEvents),
            'capabilities' => [
                'createEvents' => $user->can('create', Event::class),
                'createSeasons' => $user->can('create', Season::class),
                'viewUsers' => $user->can(Permission::ViewUsers->value),
            ],
            'stats' => [
                'draftEvents' => $eventSummary['drafts'],
                'upcomingEvents' => $eventSummary['upcoming'],
                'recentChanges' => $eventSummary['recent'] + $seasonSummary['recent'],
            ],
            'openPoints' => [
                'draftEvents' => $eventSummary['drafts'],
                'closedRegistrationEvents' => $eventSummary['closedRegistrations'],
                'expiredRegistrationEvents' => $eventSummary['expiredRegistrations'],
                'missingContentEvents' => $eventSummary['withoutContent'],
                'missingCoverEvents' => $eventSummary['withoutCover'],
                'unassignedUpcomingEvents' => $eventSummary['withoutSeason'],
            ],
            'nextEvent' => $canViewEvents ? $this->nextEvent($referenceTime) : null,
            'recentChanges' => $this->recentChanges($canViewEvents, $canManageSeasons),
            'isEmpty' => $eventSummary['total'] + $seasonSummary['total'] === 0,
        ]);
    }

    /**
     * @return array{
     *     events: bool,
     *     projects: bool,
     *     articles: bool,
     *     locations: bool,
     *     partners: bool,
     *     media: bool,
     *     users: bool,
     *     roles: bool,
     *     redirects: bool
     * }
     */
    private function resources(User $user, bool $isAdmin, bool $canViewEvents): array
    {
        return [
            'events' => $canViewEvents,
            'projects' => $isAdmin,
            'articles' => $isAdmin,
            'locations' => $isAdmin,
            'partners' => $isAdmin,
            'media' => $isAdmin,
            'users' => $user->can(Permission::ViewUsers->value),
            'roles' => $user->can(Permission::ViewRoles->value),
            'redirects' => $user->can(Permission::ViewRedirects->value),
        ];
    }

    /**
     * @return array{
     *     total: int,
     *     drafts: int,
     *     upcoming: int,
     *     recent: int,
     *     closedRegistrations: int,
     *     expiredRegistrations: int,
     *     withoutContent: int,
     *     withoutCover: int,
     *     withoutSeason: int
     * }
     */
    private function eventSummary(CarbonInterface $referenceTime, CarbonInterface $recentCutoff): array
    {
        $summary = Event::query()
            ->toBase()
            ->selectRaw('count(*) as total')
            ->selectRaw('count(case when status = ? then 1 end) as drafts', [EventStatus::Draft->value])
            ->selectRaw('count(case when starts_at >= ? and status != ? then 1 end) as upcoming', [$referenceTime, EventStatus::Cancelled->value])
            ->selectRaw('count(case when updated_at >= ? then 1 end) as recent', [$recentCutoff])
            ->selectRaw('count(case when starts_at >= ? and status = ? and registration_status = ? then 1 end) as closed_registrations', [
                $referenceTime,
                EventStatus::Published->value,
                EventRegistrationStatus::Closed->value,
            ])
            ->selectRaw('count(case when starts_at >= ? and status != ? and registration_status = ? and registration_deadline_at < ? then 1 end) as expired_registrations', [
                $referenceTime,
                EventStatus::Cancelled->value,
                EventRegistrationStatus::Open->value,
                $referenceTime,
            ])
            ->selectRaw('count(case when starts_at >= ? and status != ? and (content is null or content = ?) then 1 end) as without_content', [
                $referenceTime,
                EventStatus::Cancelled->value,
                '',
            ])
            ->selectRaw('count(case when starts_at >= ? and status != ? and cover_image_id is null then 1 end) as without_cover', [
                $referenceTime,
                EventStatus::Cancelled->value,
            ])
            ->selectRaw('count(case when starts_at >= ? and status != ? and season_id is null then 1 end) as without_season', [
                $referenceTime,
                EventStatus::Cancelled->value,
            ])
            ->first();

        return [
            'total' => (int) $summary->total,
            'drafts' => (int) $summary->drafts,
            'upcoming' => (int) $summary->upcoming,
            'recent' => (int) $summary->recent,
            'closedRegistrations' => (int) $summary->closed_registrations,
            'expiredRegistrations' => (int) $summary->expired_registrations,
            'withoutContent' => (int) $summary->without_content,
            'withoutCover' => (int) $summary->without_cover,
            'withoutSeason' => (int) $summary->without_season,
        ];
    }

    /** @return array{total: int, recent: int} */
    private function seasonSummary(CarbonInterface $recentCutoff): array
    {
        $summary = Season::query()
            ->toBase()
            ->selectRaw('count(*) as total')
            ->selectRaw('count(case when updated_at >= ? then 1 end) as recent', [$recentCutoff])
            ->first();

        return [
            'total' => (int) $summary->total,
            'recent' => (int) $summary->recent,
        ];
    }

    /**
     * @return array{
     *     id: int,
     *     title: string,
     *     startsAt: string,
     *     status: string,
     *     registrationStatus: string,
     *     location: array{name: string, city: string}
     * }|null
     */
    private function nextEvent(CarbonInterface $referenceTime): ?array
    {
        $event = Event::query()
            ->select(['id', 'location_id', 'title', 'starts_at', 'status', 'registration_status'])
            ->with('location:id,name,city')
            ->where('starts_at', '>=', $referenceTime)
            ->where('status', '!=', EventStatus::Cancelled)
            ->oldest('starts_at')
            ->oldest('id')
            ->first();

        if ($event === null) {
            return null;
        }

        return [
            'id' => $event->id,
            'title' => $event->title,
            'startsAt' => $event->starts_at->toIso8601String(),
            'status' => $event->status->value,
            'registrationStatus' => $event->registration_status->value,
            'location' => [
                'name' => $event->location->name,
                'city' => $event->location->city,
            ],
        ];
    }

    /**
     * @return list<array{
     *     id: int,
     *     kind: 'event'|'season',
     *     slug: string|null,
     *     title: string,
     *     updatedAt: string,
     *     updatedBy: array{id: int, name: string}|null
     * }>
     */
    private function recentChanges(bool $canViewEvents, bool $canManageSeasons): array
    {
        $changes = [
            ...($canViewEvents
                ? Event::query()
                    ->select(['id', 'title', 'updated_by', 'updated_at'])
                    ->with('updatedBy:id,name')
                    ->latest('updated_at')
                    ->limit(5)
                    ->get()
                    ->map(fn (Event $event): array => [
                        'id' => $event->id,
                        'kind' => 'event',
                        'slug' => null,
                        'title' => $event->title,
                        'updatedAt' => $event->updated_at->toIso8601String(),
                        'updatedBy' => $this->actor($event->updatedBy),
                        'sortAt' => $event->updated_at->getTimestamp(),
                    ])
                    ->values()
                    ->all()
                : []),
            ...($canManageSeasons
                ? Season::query()
                    ->select(['id', 'name', 'slug', 'updated_by', 'updated_at'])
                    ->with('updatedBy:id,name')
                    ->latest('updated_at')
                    ->limit(5)
                    ->get()
                    ->map(fn (Season $season): array => [
                        'id' => $season->id,
                        'kind' => 'season',
                        'slug' => $season->slug,
                        'title' => $season->name,
                        'updatedAt' => $season->updated_at->toIso8601String(),
                        'updatedBy' => $this->actor($season->updatedBy),
                        'sortAt' => $season->updated_at->getTimestamp(),
                    ])
                    ->values()
                    ->all()
                : []),
        ];

        usort(
            $changes,
            fn (array $left, array $right): int => $right['sortAt'] <=> $left['sortAt'],
        );

        return array_map(
            fn (array $change): array => [
                'id' => $change['id'],
                'kind' => $change['kind'],
                'slug' => $change['slug'],
                'title' => $change['title'],
                'updatedAt' => $change['updatedAt'],
                'updatedBy' => $change['updatedBy'],
            ],
            array_slice($changes, 0, 5),
        );
    }

    /** @return array{id: int, name: string}|null */
    private function actor(?User $user): ?array
    {
        if ($user === null) {
            return null;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
        ];
    }

    /**
     * @return array{
     *     total: int,
     *     drafts: int,
     *     upcoming: int,
     *     recent: int,
     *     closedRegistrations: int,
     *     expiredRegistrations: int,
     *     withoutContent: int,
     *     withoutCover: int,
     *     withoutSeason: int
     * }
     */
    private function emptyEventSummary(): array
    {
        return [
            'total' => 0,
            'drafts' => 0,
            'upcoming' => 0,
            'recent' => 0,
            'closedRegistrations' => 0,
            'expiredRegistrations' => 0,
            'withoutContent' => 0,
            'withoutCover' => 0,
            'withoutSeason' => 0,
        ];
    }
}
