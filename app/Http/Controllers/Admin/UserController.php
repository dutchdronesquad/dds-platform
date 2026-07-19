<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\DeleteUser;
use App\Actions\Admin\UpdateUser;
use App\Enums\Permission;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role as RoleModel;

final class UserController extends Controller
{
    private const int RECENT_ACTIVITY_DAYS = 7;

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', User::class);
        $filters = $this->filters($request);

        return Inertia::render('admin/users/index', [
            'users' => fn () => $this->users($filters),
            'filters' => $filters,
            'facets' => fn (): array => $this->facets($filters['search']),
            'summary' => fn (): array => $this->summary(),
        ]);
    }

    public function edit(Request $request, User $user): Response
    {
        Gate::authorize('update', $user);

        $editableUser = $this->editableUser($user);

        return Inertia::render('admin/users/edit', [
            'user' => [
                ...$editableUser,
                'isCurrentUser' => $request->user()?->is($user) === true,
                'capabilities' => [
                    'block' => ! $editableUser['isSoleActiveAdmin']
                        && $request->user()?->can('block', $user) === true,
                    'unblock' => $request->user()?->can('unblock', $user) === true,
                    'delete' => $request->user()?->can('delete', $user) === true,
                ],
            ],
            'roleOptions' => $this->roleOptions(),
            'localeOptions' => $this->localeOptions(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user, UpdateUser $updateUser): RedirectResponse
    {
        $updatedUser = $updateUser->handle($user, $request->userData());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Gebruiker bijgewerkt.']);

        if ($request->user()->is($user)
            && ! $updatedUser->can(Permission::UpdateUsers->value)) {
            return to_route('dashboard');
        }

        return to_route('admin.users.edit', $user);
    }

    public function destroy(User $user, DeleteUser $deleteUser): RedirectResponse
    {
        Gate::authorize('delete', $user);
        $deleteUser->handle($user);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Gebruiker verwijderd.']);

        return to_route('admin.users.index');
    }

    /** @return array{search: string, role: list<string>, verification: list<string>, account: list<string>, activity: list<string>} */
    private function filters(Request $request): array
    {
        return [
            'search' => Str::substr($request->string('search')->trim()->toString(), 0, 100),
            'role' => $this->allowedValues($request->array('role'), [...array_column(Role::cases(), 'value'), 'none']),
            'verification' => $this->allowedValues($request->array('verification'), ['verified', 'unverified']),
            'account' => $this->allowedValues($request->array('account'), ['active', 'inactive']),
            'activity' => $this->allowedValues($request->array('activity'), ['recent', 'never']),
        ];
    }

    /**
     * @param  array{search: string, role: list<string>, verification: list<string>, account: list<string>, activity: list<string>}  $filters
     * @return LengthAwarePaginator<int, covariant array{id: int, name: string, email: string, locale: string, roles: list<string>, isActive: bool, emailVerifiedAt: string|null, lastActiveAt: string|null, createdAt: string}>
     */
    private function users(array $filters): LengthAwarePaginator
    {
        $query = $this->userQuery();
        $this->applySearch($query, $filters['search']);
        $this->applyFilters($query, $filters);

        return $query
            ->latest('updated_at')
            ->paginate(25)
            ->appends(array_filter($filters, fn (mixed $value): bool => $value !== '' && $value !== []))
            ->through(fn (User $user): array => $this->userListData($user));
    }

    /**
     * @param  Builder<User>  $query
     * @param  array{search: string, role: list<string>, verification: list<string>, account: list<string>, activity: list<string>}  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        $this->applyRoleFilter($query, $filters['role']);

        $query->when($filters['verification'] !== [], function (Builder $query) use ($filters): void {
            $query->where(function (Builder $query) use ($filters): void {
                if (in_array('verified', $filters['verification'], true)) {
                    $query->orWhereNotNull('email_verified_at');
                }

                if (in_array('unverified', $filters['verification'], true)) {
                    $query->orWhereNull('email_verified_at');
                }
            });
        });

        $query->when($filters['account'] !== [], fn (Builder $query): Builder => $query
            ->whereIn('is_active', array_map(
                fn (string $status): bool => $status === 'active',
                $filters['account'],
            )));

        $this->applyActivityFilter($query, $filters['activity']);
    }

    /**
     * @param  Builder<User>  $query
     * @param  list<string>  $roles
     */
    private function applyRoleFilter(Builder $query, array $roles): void
    {
        if ($roles === []) {
            return;
        }

        $assignedRoles = array_values(array_diff($roles, ['none']));

        $query->where(function (Builder $query) use ($assignedRoles, $roles): void {
            if ($assignedRoles !== []) {
                $query->whereHas('roles', fn (Builder $query): Builder => $query->whereIn('name', $assignedRoles));
            }

            if (in_array('none', $roles, true)) {
                $method = $assignedRoles === [] ? 'whereDoesntHave' : 'orWhereDoesntHave';
                $query->{$method}('roles');
            }
        });
    }

    /**
     * @param  Builder<User>  $query
     * @param  list<string>  $activity
     */
    private function applyActivityFilter(Builder $query, array $activity): void
    {
        if ($activity === []) {
            return;
        }

        $query->where(function (Builder $query) use ($activity): void {
            if (in_array('recent', $activity, true)) {
                $query->whereExists(fn (QueryBuilder $sessions): QueryBuilder => $this->sessionsForUser($sessions)
                    ->where('last_activity', '>=', now()->subDays(self::RECENT_ACTIVITY_DAYS)->timestamp));
            }

            if (in_array('never', $activity, true)) {
                $method = in_array('recent', $activity, true) ? 'orWhereNotExists' : 'whereNotExists';
                $query->{$method}(fn (QueryBuilder $sessions): QueryBuilder => $this->sessionsForUser($sessions));
            }
        });
    }

    /** @return Builder<User> */
    private function userQuery(): Builder
    {
        return User::query()
            ->select(['id', 'name', 'email', 'locale', 'is_active', 'email_verified_at', 'created_at', 'updated_at'])
            ->selectSub(fn (QueryBuilder $sessions): QueryBuilder => $this->sessionsForUser($sessions)->selectRaw('MAX(last_activity)'), 'last_activity')
            ->with('roles:id,name');
    }

    private function sessionsForUser(QueryBuilder $query): QueryBuilder
    {
        return $query
            ->from((string) config('session.table', 'sessions'))
            ->whereColumn('user_id', (new User)->qualifyColumn('id'));
    }

    /** @param Builder<User> $query */
    private function applySearch(Builder $query, string $search): void
    {
        if ($search === '') {
            return;
        }

        $searchPattern = '%'.Str::lower($search).'%';
        $query->where(function (Builder $query) use ($searchPattern): void {
            $query
                ->whereRaw('LOWER(name) LIKE ?', [$searchPattern])
                ->orWhereRaw('LOWER(email) LIKE ?', [$searchPattern]);
        });
    }

    /** @return array{roles: array<string, int>, verified: int, unverified: int, active: int, inactive: int, recent: int, never: int} */
    private function facets(string $search): array
    {
        $query = User::query();
        $this->applySearch($query, $search);
        $recentCutoff = now()->subDays(self::RECENT_ACTIVITY_DAYS)->timestamp;

        return [
            'roles' => [
                Role::Admin->value => (clone $query)->role(Role::Admin->value)->count(),
                Role::Editor->value => (clone $query)->role(Role::Editor->value)->count(),
                'none' => (clone $query)->doesntHave('roles')->count(),
            ],
            'verified' => (clone $query)->whereNotNull('email_verified_at')->count(),
            'unverified' => (clone $query)->whereNull('email_verified_at')->count(),
            'active' => (clone $query)->where('is_active', true)->count(),
            'inactive' => (clone $query)->where('is_active', false)->count(),
            'recent' => (clone $query)->whereExists(fn (QueryBuilder $sessions): QueryBuilder => $this->sessionsForUser($sessions)->where('last_activity', '>=', $recentCutoff))->count(),
            'never' => (clone $query)->whereNotExists(fn (QueryBuilder $sessions): QueryBuilder => $this->sessionsForUser($sessions))->count(),
        ];
    }

    /** @return array{total: int, active: int, admins: int, unverified: int} */
    private function summary(): array
    {
        return [
            'total' => User::query()->count(),
            'active' => User::query()->where('is_active', true)->count(),
            'admins' => User::query()->where('is_active', true)->role(Role::Admin->value)->count(),
            'unverified' => User::query()->whereNull('email_verified_at')->count(),
        ];
    }

    /** @return array{id: int, name: string, email: string, locale: string, roles: list<string>, isActive: bool, emailVerifiedAt: string|null, lastActiveAt: string|null, createdAt: string} */
    private function userListData(User $user): array
    {
        $roles = [];

        foreach ($user->roles as $role) {
            if ($role instanceof RoleModel) {
                $roles[] = $role->name;
            }
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'locale' => $user->locale,
            'roles' => $roles,
            'isActive' => $user->is_active,
            'emailVerifiedAt' => $user->email_verified_at?->toIso8601String(),
            'lastActiveAt' => $this->lastActivityAt($user),
            'createdAt' => $user->created_at?->toIso8601String() ?? '',
        ];
    }

    /** @return array{id: int, name: string, email: string, locale: string, roles: list<string>, isActive: bool, emailVerifiedAt: string|null, lastActiveAt: string|null, createdAt: string, isSoleActiveAdmin: bool} */
    private function editableUser(User $user): array
    {
        $editableUser = $this->userQuery()->findOrFail($user->id);

        return [
            ...$this->userListData($editableUser),
            'isSoleActiveAdmin' => $editableUser->is_active
                && $editableUser->hasRole(Role::Admin->value)
                && User::query()->where('is_active', true)->role(Role::Admin->value)->count() === 1,
        ];
    }

    private function lastActivityAt(User $user): ?string
    {
        $lastActivity = $user->getAttribute('last_activity');

        return is_numeric($lastActivity)
            ? Carbon::createFromTimestampUTC((int) $lastActivity)->toIso8601String()
            : null;
    }

    /** @return list<array{value: string, label: string, description: string}> */
    private function roleOptions(): array
    {
        return [
            ['value' => Role::Admin->value, 'label' => 'Beheerder', 'description' => 'Volledige toegang tot gebruikers en beheerfuncties.'],
            ['value' => Role::Editor->value, 'label' => 'Redacteur', 'description' => 'Toegang tot toegewezen contentfuncties, niet tot gebruikersbeheer.'],
        ];
    }

    /** @return list<array{value: string, label: string}> */
    private function localeOptions(): array
    {
        $configuredLocales = config('localization.supported_locales', []);

        if (! is_array($configuredLocales)) {
            return [];
        }

        $options = [];

        foreach ($configuredLocales as $code => $locale) {
            if (! is_string($code) || ! is_array($locale)) {
                continue;
            }

            $options[] = [
                'value' => $code,
                'label' => (string) ($locale['native_name'] ?? $locale['name'] ?? $code),
            ];
        }

        return $options;
    }

    /**
     * @param  array<mixed>  $values
     * @param  list<string>  $allowed
     * @return list<string>
     */
    private function allowedValues(array $values, array $allowed): array
    {
        $filteredValues = [];

        foreach ($allowed as $allowedValue) {
            if (in_array($allowedValue, $values, true)) {
                $filteredValues[] = $allowedValue;
            }
        }

        return $filteredValues;
    }
}
