<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ArticleStatus;
use App\Enums\EventStatus;
use App\Enums\Permission;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Event;
use App\Models\Location;
use App\Models\MediaAsset;
use App\Models\Redirect;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
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
        $canViewRedirects = $user->can(Permission::ViewRedirects->value);

        $eventSummary = $canViewEvents
            ? $this->eventSummary()
            : $this->emptySummary();
        $articleSummary = $isAdmin
            ? $this->articleSummary()
            : $this->emptySummary();
        $redirectSummary = $canViewRedirects
            ? $this->recordSummary(Redirect::query())
            : $this->emptySummary();

        $managedRecords = $eventSummary['total']
            + $articleSummary['total']
            + $redirectSummary['total'];

        if ($isAdmin) {
            $managedRecords += Location::query()->count();
            $managedRecords += MediaAsset::query()->count();
        }

        return Inertia::render('dashboard', [
            'resources' => [
                'events' => $canViewEvents,
                'projects' => $isAdmin,
                'articles' => $isAdmin,
                'locations' => $isAdmin,
                'partners' => $isAdmin,
                'media' => $isAdmin,
                'users' => $user->can(Permission::ViewUsers->value),
                'redirects' => $canViewRedirects,
            ],
            'stats' => [
                'drafts' => $eventSummary['drafts'] + $articleSummary['drafts'],
                'upcomingEvents' => $eventSummary['upcoming'],
                'recentActivity' => $eventSummary['recent']
                    + $articleSummary['recent']
                    + $redirectSummary['recent'],
            ],
            'isEmpty' => $managedRecords === 0,
        ]);
    }

    /** @return array{total: int, drafts: int, upcoming: int, recent: int} */
    private function eventSummary(): array
    {
        $summary = Event::query()
            ->toBase()
            ->selectRaw('count(*) as total')
            ->selectRaw('count(case when status = ? then 1 end) as drafts', [EventStatus::Draft->value])
            ->selectRaw('count(case when starts_at >= ? and status != ? then 1 end) as upcoming', [now(), EventStatus::Cancelled->value])
            ->selectRaw('count(case when created_at >= ? then 1 end) as recent', [now()->subDays(7)])
            ->first();

        return [
            'total' => (int) $summary->total,
            'drafts' => (int) $summary->drafts,
            'upcoming' => (int) $summary->upcoming,
            'recent' => (int) $summary->recent,
        ];
    }

    /** @return array{total: int, drafts: int, upcoming: int, recent: int} */
    private function articleSummary(): array
    {
        $summary = Article::query()
            ->toBase()
            ->selectRaw('count(*) as total')
            ->selectRaw('count(case when status = ? then 1 end) as drafts', [ArticleStatus::Draft->value])
            ->selectRaw('count(case when created_at >= ? then 1 end) as recent', [now()->subDays(7)])
            ->first();

        return [
            'total' => (int) $summary->total,
            'drafts' => (int) $summary->drafts,
            'upcoming' => 0,
            'recent' => (int) $summary->recent,
        ];
    }

    /**
     * @param  Builder<Redirect>  $query
     * @return array{total: int, drafts: int, upcoming: int, recent: int}
     */
    private function recordSummary(Builder $query): array
    {
        $summary = $query
            ->toBase()
            ->selectRaw('count(*) as total')
            ->selectRaw('count(case when created_at >= ? then 1 end) as recent', [now()->subDays(7)])
            ->first();

        return [
            'total' => (int) $summary->total,
            'drafts' => 0,
            'upcoming' => 0,
            'recent' => (int) $summary->recent,
        ];
    }

    /** @return array{total: int, drafts: int, upcoming: int, recent: int} */
    private function emptySummary(): array
    {
        return [
            'total' => 0,
            'drafts' => 0,
            'upcoming' => 0,
            'recent' => 0,
        ];
    }
}
