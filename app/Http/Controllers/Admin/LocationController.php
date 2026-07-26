<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LocationEnvironment;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLocationRequest;
use App\Http\Requests\Admin\UpdateLocationRequest;
use App\Models\Location;
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

final class LocationController extends Controller
{
    public function __construct(private MediaAssetPickerData $mediaAssetPickerData) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Location::class);

        $filters = $this->filters($request);

        return Inertia::render('admin/locations/index', [
            'locations' => fn () => $this->locations($request->user(), $filters),
            'filters' => $filters,
            'canCreate' => $request->user()->can('create', Location::class),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Location::class);

        return Inertia::render('admin/locations/create', [
            'options' => $this->formOptions(),
        ]);
    }

    public function store(StoreLocationRequest $request): RedirectResponse
    {
        $location = Location::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Locatie aangemaakt.']);

        return to_route('admin.locations.edit', $location);
    }

    public function edit(Request $request, Location $location): Response
    {
        Gate::authorize('update', $location);

        $location->loadCount('events');
        $location->load('coverImage.media');

        return Inertia::render('admin/locations/edit', [
            'location' => $this->formLocation($request->user(), $location),
            'options' => $this->formOptions(),
        ]);
    }

    public function update(UpdateLocationRequest $request, Location $location): RedirectResponse
    {
        $location->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Locatie opgeslagen.']);

        return to_route('admin.locations.edit', $location);
    }

    public function destroy(Location $location): RedirectResponse
    {
        Gate::authorize('delete', $location);

        if ($location->events()->exists()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Deze locatie kan niet worden verwijderd omdat er nog events aan gekoppeld zijn.',
            ]);

            return to_route('admin.locations.edit', $location);
        }

        $location->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Locatie verwijderd.']);

        return to_route('admin.locations.index');
    }

    /** @return array{search: string} */
    private function filters(Request $request): array
    {
        return [
            'search' => Str::substr($request->string('search')->trim()->toString(), 0, 100),
        ];
    }

    /**
     * @param  array{search: string}  $filters
     * @return LengthAwarePaginator<int, covariant array{
     *     id: int,
     *     name: string,
     *     slug: string,
     *     city: string,
     *     environment: string,
     *     eventsCount: int,
     *     activity: array{updatedAt: string},
     *     capabilities: array{update: bool, delete: bool}
     * }>
     */
    private function locations(User $user, array $filters): LengthAwarePaginator
    {
        $query = Location::query()
            ->select([
                'id',
                'name',
                'slug',
                'city',
                'environment',
                'updated_at',
            ])
            ->withCount('events');

        $this->applySearch($query, $filters['search']);

        return $query
            ->orderBy('name')
            ->paginate(25)
            ->appends(array_filter([
                'search' => $filters['search'] !== '' ? $filters['search'] : null,
            ], fn (mixed $value): bool => $value !== null))
            ->through(fn (Location $location): array => [
                'id' => $location->id,
                'name' => $location->name,
                'slug' => $location->slug,
                'city' => $location->city,
                'environment' => $location->environment->value,
                'eventsCount' => (int) $location->getAttribute('events_count'),
                'activity' => [
                    'updatedAt' => $location->updated_at->toIso8601String(),
                ],
                'capabilities' => [
                    'update' => $user->can('update', $location),
                    'delete' => $user->can('delete', $location) && $location->getAttribute('events_count') === 0,
                ],
            ]);
    }

    /** @param Builder<Location> $query */
    private function applySearch(Builder $query, string $search): void
    {
        if ($search === '') {
            return;
        }

        $searchPattern = '%'.Str::lower($search).'%';

        $query->where(function (Builder $query) use ($searchPattern): void {
            $query
                ->whereRaw('LOWER(name) LIKE ?', [$searchPattern])
                ->orWhereRaw('LOWER(city) LIKE ?', [$searchPattern]);
        });
    }

    /** @return array<string, mixed> */
    private function formOptions(): array
    {
        return [
            'environments' => $this->environmentOptions(),
        ];
    }

    /** @return array<string, mixed> */
    private function formLocation(User $user, Location $location): array
    {
        $eventsCount = (int) $location->getAttribute('events_count');

        return [
            'id' => $location->id,
            'name' => $location->name,
            'slug' => $location->slug,
            'description' => $location->description,
            'street' => $location->street,
            'houseNumber' => $location->house_number,
            'postalCode' => $location->postal_code,
            'city' => $location->city,
            'countryCode' => $location->country_code,
            'environment' => $location->environment->value,
            'floorSizeSquareMetres' => $location->floor_size_square_metres,
            'ceilingHeightMetres' => $location->ceiling_height_metres,
            'facilities' => $location->facilities ?? [],
            'websiteUrl' => $location->website_url,
            'latitude' => $location->latitude,
            'longitude' => $location->longitude,
            'coverImageId' => $location->cover_image_id,
            'coverImage' => $location->coverImage === null
                ? null
                : $this->mediaAssetPickerData->one($location->coverImage),
            'eventsCount' => $eventsCount,
            'activity' => [
                'createdAt' => $location->created_at->toIso8601String(),
                'createdBy' => null,
                'updatedAt' => $location->updated_at->toIso8601String(),
                'updatedBy' => null,
            ],
            'capabilities' => [
                'delete' => $user->can('delete', $location) && $eventsCount === 0,
            ],
        ];
    }

    /** @return list<array{value: string, label: string}> */
    private function environmentOptions(): array
    {
        return [
            ['value' => LocationEnvironment::Indoor->value, 'label' => 'Indoor'],
            ['value' => LocationEnvironment::Outdoor->value, 'label' => 'Outdoor'],
        ];
    }
}
