<?php

namespace App\Http\Middleware;

use App\Enums\ContactDeliveryStatus;
use App\Enums\Permission;
use App\Models\Article;
use App\Models\ContactSubmission;
use App\Models\Event;
use App\Models\MediaAsset;
use App\Models\Season;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    private const int AUTH_PHOTO_ROTATION_INTERVAL = 7000;

    private const int TEST_AUTH_PHOTO_ROTATION_INTERVAL = 250;

    private const int SIDEBAR_COUNT_CACHE_SECONDS = 30;

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
            'management' => fn (): ?array => $this->managementProps($request),
            'ui' => [
                'authPhotoRotationInterval' => app()->environment('testing')
                    ? self::TEST_AUTH_PHOTO_ROTATION_INTERVAL
                    : self::AUTH_PHOTO_ROTATION_INTERVAL,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function managementProps(Request $request): ?array
    {
        $user = $request->user();

        if (! $user) {
            return null;
        }

        $canViewContact = $user->can(Permission::ViewContact->value);
        $canViewEvents = $user->can(Permission::ViewEvents->value);
        $canViewArticles = $user->can(Permission::ViewArticles->value);
        $canViewUsers = $user->can(Permission::ViewUsers->value);

        return [
            'events' => [
                'canView' => $canViewEvents,
                'count' => $canViewEvents ? $this->eventCount() : 0,
            ],
            'locations' => [
                'canView' => $user->can(Permission::ViewLocations->value),
            ],
            'articles' => [
                'canView' => $canViewArticles,
                'count' => $canViewArticles ? $this->articleCount() : 0,
            ],
            'media' => [
                'canView' => $user->can('viewAny', MediaAsset::class),
            ],
            'seasons' => [
                'canManage' => $user->can('viewAny', Season::class),
            ],
            'redirects' => [
                'canView' => $user->can(Permission::ViewRedirects->value),
            ],
            'contact' => [
                'canView' => $canViewContact,
                'followUpCount' => $canViewContact ? $this->contactFollowUpCount() : 0,
            ],
            'users' => [
                'canView' => $canViewUsers,
                'count' => $canViewUsers ? $this->userCount() : 0,
            ],
            'roles' => [
                'canView' => $user->can(Permission::ViewRoles->value),
            ],
        ];
    }

    private function contactFollowUpCount(): int
    {
        return $this->cachedSidebarCount(
            'contact_follow_up',
            fn (): int => ContactSubmission::query()
                ->whereIn('delivery_status', [
                    ContactDeliveryStatus::NotConfigured,
                    ContactDeliveryStatus::Failed,
                ])
                ->count(),
        );
    }

    private function eventCount(): int
    {
        return $this->cachedSidebarCount('events', fn (): int => Event::query()->count());
    }

    private function articleCount(): int
    {
        return $this->cachedSidebarCount('articles', fn (): int => Article::query()->count());
    }

    private function userCount(): int
    {
        return $this->cachedSidebarCount('users', fn (): int => User::query()->count());
    }

    private function cachedSidebarCount(string $key, callable $query): int
    {
        return Cache::remember(
            "sidebar-counts.{$key}",
            self::SIDEBAR_COUNT_CACHE_SECONDS,
            $query,
        );
    }
}
