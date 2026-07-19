<?php

use App\Enums\Role as RoleEnum;
use App\Models\Redirect;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('guests are redirected to login from the redirect review', function () {
    $this->get(route('redirects.index'))->assertRedirect(route('login'));
});

test('users without a management role cannot review redirects', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('redirects.index'))
        ->assertForbidden();
});

test('admins can review redirect status and usage', function () {
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::Admin->value);

    Redirect::factory()->create([
        'source_path' => '/old-news',
        'target_url' => '/news',
        'hit_count' => 12,
        'notes' => 'Imported from WordPress.',
    ]);

    $this->actingAs($user)
        ->get(route('redirects.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/redirects/index')
            ->where('summary.total', 1)
            ->where('summary.active', 1)
            ->where('summary.hits', 12)
            ->where('facets.active', 1)
            ->where('facets.inactive', 0)
            ->where('redirects.current_page', 1)
            ->where('redirects.total', 1)
            ->has('redirects.data', 1)
            ->where('redirects.data.0.sourcePath', '/old-news')
            ->where('redirects.data.0.targetUrl', '/news')
            ->where('redirects.data.0.statusCode', 301)
            ->where('redirects.data.0.isActive', true)
            ->where('redirects.data.0.hitCount', 12)
            ->where('redirects.data.0.notes', 'Imported from WordPress.')
            ->has('redirects.data.0.updatedAt'),
        );
});

test('redirect review is paginated while its summary covers every record', function () {
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::Admin->value);

    Redirect::factory()->count(51)->create(['hit_count' => 1]);

    $this->actingAs($user)
        ->get(route('redirects.index', ['page' => 2]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.total', 51)
            ->where('summary.active', 51)
            ->where('summary.hits', 51)
            ->where('redirects.current_page', 2)
            ->where('redirects.last_page', 2)
            ->where('redirects.total', 51)
            ->where('redirects.prev_page_url', fn (string $url): bool => ! str_contains($url, 'search=') && ! str_contains($url, 'status='))
            ->has('redirects.data', 1),
        );
});

test('admins can search redirects without changing the overall summary', function () {
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::Admin->value);

    Redirect::factory()->create([
        'source_path' => '/oude-kalender',
        'target_url' => '/events',
        'notes' => 'Belangrijke migratie',
    ]);
    Redirect::factory()->create([
        'source_path' => '/oude-contactpagina',
        'target_url' => '/contact',
    ]);
    Redirect::factory()->inactive()->create([
        'source_path' => '/kalender-archief',
        'target_url' => '/archive',
    ]);

    $this->actingAs($user)
        ->get(route('redirects.index', ['search' => 'KALENDER']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.search', 'KALENDER')
            ->where('filters.status', [])
            ->where('summary.total', 3)
            ->where('facets.active', 1)
            ->where('facets.inactive', 1)
            ->where('redirects.total', 2)
            ->where('redirects.data', fn (Collection $redirects): bool => $redirects
                ->pluck('sourcePath')
                ->sort()
                ->values()
                ->all() === ['/kalender-archief', '/oude-kalender']),
        );
});

test('redirect searches are hard limited without changing the search term', function () {
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::Admin->value);
    $search = str_repeat('a', 120);

    $this->actingAs($user)
        ->get(route('redirects.index', ['search' => $search]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.search', str_repeat('a', 100))
            ->where('redirects.total', 0),
        );
});

test('zero remains a valid search value in pagination links', function () {
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::Admin->value);

    Redirect::factory()->count(51)->create(['target_url' => '/0']);

    $this->actingAs($user)
        ->get(route('redirects.index', ['search' => '0']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.search', '0')
            ->where('redirects.total', 51)
            ->where('redirects.next_page_url', fn (string $url): bool => str_contains($url, 'search=0')),
        );
});

test('admins can filter redirects by active status and keep filters in pagination links', function () {
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::Admin->value);

    Redirect::factory()->count(51)->create();
    Redirect::factory()->inactive()->create();

    $this->actingAs($user)
        ->get(route('redirects.index', ['status' => 'active']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.search', '')
            ->where('filters.status', ['active'])
            ->where('summary.total', 52)
            ->where('summary.active', 51)
            ->where('facets.active', 51)
            ->where('facets.inactive', 1)
            ->where('redirects.total', 51)
            ->where('redirects.last_page', 2)
            ->where('redirects.next_page_url', function (string $url): bool {
                parse_str(parse_url($url, PHP_URL_QUERY) ?: '', $query);

                return $query['status'] === ['active'];
            }),
        );
});

test('admins can combine multiple redirect status facets', function () {
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::Admin->value);

    Redirect::factory()->create();
    Redirect::factory()->inactive()->create();

    $this->actingAs($user)
        ->get(route('redirects.index', ['status' => ['active', 'inactive']]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.status', ['active', 'inactive'])
            ->where('redirects.total', 2),
        );
});

test('unknown redirect statuses fall back to the unfiltered list', function () {
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::Admin->value);

    Redirect::factory()->count(51)->create();
    Redirect::factory()->inactive()->create();

    $this->actingAs($user)
        ->get(route('redirects.index', ['status' => 'unknown']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.status', [])
            ->where('redirects.total', 52)
            ->where('redirects.next_page_url', fn (string $url): bool => ! str_contains($url, 'status=')),
        );
});

test('editors can review redirects through their permission', function () {
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::Editor->value);

    $this->actingAs($user)
        ->get(route('redirects.index'))
        ->assertOk();
});
