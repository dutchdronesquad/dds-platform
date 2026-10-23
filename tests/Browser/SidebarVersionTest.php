<?php

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Vite;

test('the sidebar displays the build version and hides it when collapsed', function () {
    Vite::useHotFile(storage_path('framework/testing/vite.hot'));
    config(['app.version' => 'v1.4.0']);
    $this->seed(RolesAndPermissionsSeeder::class);
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);
    $this->actingAs($user);

    $page = visit(route('dashboard'))
        ->on()->desktop()
        ->assertSee('v1.4.0')
        ->assertVisible('[data-testid="sidebar-version"]')
        ->assertNoJavaScriptErrors()
        ->screenshot(filename: 'sidebar-release-version', fullPage: false);

    $page->click('button[data-sidebar="trigger"]')
        ->assertScript('getComputedStyle(document.querySelector("[data-testid=sidebar-version]")).display === "none"')
        ->assertNoJavaScriptErrors();
});
