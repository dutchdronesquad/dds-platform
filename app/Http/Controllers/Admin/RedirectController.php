<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Redirect;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

final class RedirectController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $this->filters($request);

        return Inertia::render('admin/redirects/index', [
            'redirects' => fn (): LengthAwarePaginator => $this->redirects($filters),
            'filters' => $filters,
            'facets' => fn (): array => $this->facets($filters['search']),
            'summary' => fn (): array => $this->summary(),
        ]);
    }

    /** @return array{search: string, status: 'active'|'all'|'inactive'} */
    private function filters(Request $request): array
    {
        $search = $request->string('search')->trim()->limit(100)->toString();
        $status = $request->string('status')->toString();

        if (! in_array($status, ['active', 'inactive'], true)) {
            $status = 'all';
        }

        return [
            'search' => $search,
            'status' => $status,
        ];
    }

    /**
     * @param  array{search: string, status: 'active'|'all'|'inactive'}  $filters
     * @return LengthAwarePaginator<int, array<string, bool|int|string|null>>
     */
    private function redirects(array $filters): LengthAwarePaginator
    {
        $queryParameters = [
            'search' => $filters['search'] !== '' ? $filters['search'] : null,
            'status' => $filters['status'] !== 'all' ? $filters['status'] : null,
        ];

        $query = Redirect::query()->select([
            'id',
            'source_path',
            'target_url',
            'status_code',
            'is_active',
            'hit_count',
            'notes',
            'updated_at',
        ]);

        $this->applySearch($query, $filters['search']);

        return $query
            ->when($filters['status'] !== 'all', fn (Builder $query): Builder => $query
                ->where('is_active', $filters['status'] === 'active'))
            ->latest('updated_at')
            ->paginate(50)
            ->appends(array_filter($queryParameters))
            ->through(fn (Redirect $redirect): array => [
                'id' => $redirect->id,
                'sourcePath' => $redirect->source_path,
                'targetUrl' => $redirect->target_url,
                'statusCode' => $redirect->status_code,
                'isActive' => $redirect->is_active,
                'hitCount' => $redirect->hit_count,
                'notes' => $redirect->notes,
                'updatedAt' => $redirect->updated_at->toIso8601String(),
            ]);
    }

    /** @return array{active: int, inactive: int} */
    private function facets(string $search): array
    {
        $query = Redirect::query();

        $this->applySearch($query, $search);

        $counts = $query
            ->toBase()
            ->selectRaw('SUM(CASE WHEN is_active THEN 1 ELSE 0 END) AS active')
            ->selectRaw('SUM(CASE WHEN NOT is_active THEN 1 ELSE 0 END) AS inactive')
            ->first();

        return [
            'active' => (int) $counts?->active,
            'inactive' => (int) $counts?->inactive,
        ];
    }

    private function applySearch(Builder $query, string $search): void
    {
        if ($search === '') {
            return;
        }

        $searchPattern = '%'.Str::lower($search).'%';

        $query->where(function (Builder $query) use ($searchPattern): void {
            $query
                ->whereRaw('LOWER(source_path) LIKE ?', [$searchPattern])
                ->orWhereRaw('LOWER(target_url) LIKE ?', [$searchPattern])
                ->orWhereRaw('LOWER(COALESCE(notes, ?)) LIKE ?', ['', $searchPattern]);
        });
    }

    /** @return array{total: int, active: int, hits: int} */
    private function summary(): array
    {
        return [
            'total' => Redirect::query()->count(),
            'active' => Redirect::query()->active()->count(),
            'hits' => (int) Redirect::query()->sum('hit_count'),
        ];
    }
}
