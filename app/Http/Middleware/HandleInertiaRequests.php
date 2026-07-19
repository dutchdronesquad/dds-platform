<?php

namespace App\Http\Middleware;

use App\Enums\Permission;
use App\Models\Season;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'locale' => [
                'active' => app()->getLocale(),
                'supported' => config('localization.supported_locales'),
                'default' => config('localization.default_locale'),
                'fallback' => config('localization.fallback_locale'),
                'usesRoutePrefixes' => config('localization.use_locale_route_prefixes'),
            ],
            'auth' => [
                'user' => $request->user(),
            ],
            'management' => fn (): ?array => $request->user() ? [
                'canViewEvents' => $request->user()->can(Permission::ViewEvents->value),
                'canManageSeasons' => $request->user()->can('viewAny', Season::class),
                'canViewRedirects' => $request->user()->can(Permission::ViewRedirects->value),
            ] : null,
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
