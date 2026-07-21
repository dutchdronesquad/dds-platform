<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\DeleteMediaAsset;
use App\Actions\Admin\StoreMediaAsset;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMediaAssetRequest;
use App\Http\Requests\Admin\UpdateMediaAssetRequest;
use App\Models\Article;
use App\Models\Event;
use App\Models\Location;
use App\Models\MediaAsset;
use App\Models\User;
use App\Support\MediaAssetPickerData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

final class MediaAssetController extends Controller
{
    public function __construct(private MediaAssetPickerData $pickerData) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', MediaAsset::class);

        $filters = $this->filters($request);

        return Inertia::render('admin/media/index', [
            'mediaAssets' => fn () => $this->mediaAssets($request->user(), $filters),
            'filters' => $filters,
            'summary' => fn (): array => $this->summary(),
            'canCreate' => $request->user()->can('create', MediaAsset::class),
            'locales' => $this->locales(),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', MediaAsset::class);

        return Inertia::render('admin/media/create', [
            'locales' => $this->locales(),
        ]);
    }

    public function store(StoreMediaAssetRequest $request, StoreMediaAsset $storeMediaAsset): RedirectResponse
    {
        $storeMediaAsset->handle($request->file('file'), $request->altText());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Bestand toegevoegd aan de mediabibliotheek.']);

        return to_route('admin.media.index');
    }

    public function show(Request $request, MediaAsset $mediaAsset): JsonResponse
    {
        Gate::authorize('view', $mediaAsset);

        $mediaAsset->load([
            'media',
            'coverEvents:id,title,cover_image_id',
            'coverArticles:id,title,cover_image_id',
            'coverLocations:id,name,cover_image_id',
        ])->loadCount(['coverEvents', 'coverArticles', 'coverLocations']);

        return response()->json([
            'data' => [
                ...$this->mediaData($request->user(), $mediaAsset),
                'path' => $mediaAsset->storagePath(),
                'usage' => $this->usage($mediaAsset),
            ],
        ]);
    }

    public function update(UpdateMediaAssetRequest $request, MediaAsset $mediaAsset): RedirectResponse
    {
        $mediaAsset->update(['alt_text' => $request->altText()]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Mediametadata opgeslagen.']);

        return back();
    }

    public function destroy(MediaAsset $mediaAsset, DeleteMediaAsset $deleteMediaAsset): RedirectResponse
    {
        Gate::authorize('delete', $mediaAsset);

        $deleteMediaAsset->handle($mediaAsset);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Media-item en bestand definitief verwijderd.']);

        return to_route('admin.media.index');
    }

    /** @return array{search: string, category: string, usage: string, status: string} */
    private function filters(Request $request): array
    {
        $category = $request->string('category')->toString();
        $usage = $request->string('usage')->toString();
        $status = $request->string('status', 'active')->toString();

        return [
            'search' => Str::substr($request->string('search')->trim()->toString(), 0, 100),
            'category' => in_array($category, ['image', 'document'], true) ? $category : 'all',
            'usage' => in_array($usage, ['used', 'unused'], true) ? $usage : 'all',
            'status' => in_array($status, ['active', 'archived', 'all'], true) ? $status : 'active',
        ];
    }

    /**
     * @param  array{search: string, category: string, usage: string, status: string}  $filters
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    private function mediaAssets(User $user, array $filters): LengthAwarePaginator
    {
        $query = MediaAsset::query()
            ->select([
                'id',
                'alt_text',
                'archived_at',
                'created_at',
                'updated_at',
            ])
            ->with('media')
            ->withCount(['coverEvents', 'coverArticles', 'coverLocations']);

        $this->applySearch($query, $filters['search']);
        $this->applyUsageFilter($query, $filters['usage']);

        return $query
            ->when($filters['category'] === 'image', fn (Builder $query): Builder => $query
                ->withMimeType('like', 'image/%'))
            ->when($filters['category'] === 'document', fn (Builder $query): Builder => $query
                ->withMimeType('=', 'application/pdf'))
            ->when($filters['status'] === 'active', fn (Builder $query): Builder => $query
                ->whereNull('archived_at'))
            ->when($filters['status'] === 'archived', fn (Builder $query): Builder => $query
                ->whereNotNull('archived_at'))
            ->latest()
            ->paginate(24)
            ->withQueryString()
            ->through(fn (MediaAsset $mediaAsset): array => $this->mediaData($user, $mediaAsset));
    }

    /** @param Builder<MediaAsset> $query */
    private function applySearch(Builder $query, string $search): void
    {
        if ($search === '') {
            return;
        }

        $searchPattern = '%'.Str::lower($search).'%';

        $query->where(function (Builder $query) use ($searchPattern): void {
            $query
                ->whereRaw('LOWER(CAST(alt_text AS TEXT)) LIKE ?', [$searchPattern])
                ->orWhereHas('media', function (Builder $mediaQuery) use ($searchPattern): void {
                    $mediaQuery
                        ->whereRaw('LOWER(name) LIKE ?', [$searchPattern])
                        ->orWhereRaw('LOWER(file_name) LIKE ?', [$searchPattern]);
                });
        });
    }

    /** @param Builder<MediaAsset> $query */
    private function applyUsageFilter(Builder $query, string $usage): void
    {
        if ($usage === 'used') {
            $query->where(function (Builder $query): void {
                $query
                    ->whereHas('coverEvents')
                    ->orWhereHas('coverArticles')
                    ->orWhereHas('coverLocations');
            });
        }

        if ($usage === 'unused') {
            $query
                ->whereDoesntHave('coverEvents')
                ->whereDoesntHave('coverArticles')
                ->whereDoesntHave('coverLocations');
        }
    }

    /** @return array{total: int, images: int, documents: int, unused: int, archived: int} */
    private function summary(): array
    {
        $available = MediaAsset::query()->available();

        return [
            'total' => (clone $available)->count(),
            'images' => (clone $available)->withMimeType('like', 'image/%')->count(),
            'documents' => (clone $available)->withMimeType('=', 'application/pdf')->count(),
            'unused' => (clone $available)
                ->whereDoesntHave('coverEvents')
                ->whereDoesntHave('coverArticles')
                ->whereDoesntHave('coverLocations')
                ->count(),
            'archived' => MediaAsset::query()->whereNotNull('archived_at')->count(),
        ];
    }

    /** @return array<string, mixed> */
    private function mediaData(User $user, MediaAsset $mediaAsset): array
    {
        $usageCount = (int) ($mediaAsset->cover_events_count ?? 0)
            + (int) ($mediaAsset->cover_articles_count ?? 0)
            + (int) ($mediaAsset->cover_locations_count ?? 0);

        return [
            ...$this->pickerData->one($mediaAsset),
            'sizeBytes' => $mediaAsset->sizeBytes(),
            'createdAt' => $mediaAsset->created_at->toIso8601String(),
            'updatedAt' => $mediaAsset->updated_at->toIso8601String(),
            'usageCount' => $usageCount,
            'capabilities' => [
                'update' => $user->can('update', $mediaAsset),
                'delete' => $user->can('delete', $mediaAsset) && $usageCount === 0,
            ],
        ];
    }

    /** @return list<array{type: string, label: string, href: string|null}> */
    private function usage(MediaAsset $mediaAsset): array
    {
        return [
            ...$mediaAsset->coverEvents->map(fn (Event $event): array => [
                'type' => 'Event',
                'label' => $event->title,
                'href' => route('admin.events.edit', $event),
            ])->all(),
            ...$mediaAsset->coverArticles->map(fn (Article $article): array => [
                'type' => 'Artikel',
                'label' => $article->title,
                'href' => null,
            ])->all(),
            ...$mediaAsset->coverLocations->map(fn (Location $location): array => [
                'type' => 'Locatie',
                'label' => $location->name,
                'href' => null,
            ])->all(),
        ];
    }

    /** @return list<array{code: string, label: string}> */
    private function locales(): array
    {
        $configuredLocales = config('localization.supported_locales');

        if (! is_array($configuredLocales)) {
            return [];
        }

        $locales = [];

        foreach ($configuredLocales as $code => $locale) {
            if (! is_string($code) || ! is_array($locale) || ! is_string($locale['native_name'] ?? null)) {
                continue;
            }

            $locales[] = [
                'code' => $code,
                'label' => $locale['native_name'],
            ];
        }

        return $locales;
    }
}
