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
            ->has('redirects', 1)
            ->where('redirects.0.sourcePath', '/old-news')
            ->where('redirects.0.targetUrl', '/news')
            ->where('redirects.0.statusCode', 301)
            ->where('redirects.0.isActive', true)
            ->where('redirects.0.hitCount', 12)
            ->where('redirects.0.notes', 'Imported from WordPress.')
            ->has('redirects.0.updatedAt'),
        );
});

test('editors can review redirects through their permission', function () {
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::Editor->value);

    $this->actingAs($user)
        ->get(route('redirects.index'))
        ->assertOk();
});
