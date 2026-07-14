<?php

use App\Enums\Role as RoleEnum;
use App\Models\Redirect;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
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
            ->has('redirects.data', 1),
        );
});

test('editors can review redirects through their permission', function () {
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::Editor->value);

    $this->actingAs($user)
        ->get(route('redirects.index'))
        ->assertOk();
});
