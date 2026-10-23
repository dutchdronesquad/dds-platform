<?php

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Vite;

beforeEach(function () {
    Vite::useHotFile(storage_path('framework/testing/vite.hot'));
    config(['app.version' => 'v1.4.0']);
});

test('the desktop logo exposes the version on hover and keyboard focus without a sidebar version row', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);
    $this->actingAs($user);

    $page = visit(route('dashboard'))
        ->on()->desktop()
        ->assertMissing('[data-testid="sidebar-version"]')
        ->assertDontSee('Platformversie v1.4.0')
        ->keys('[class~="font-sans"]', 'Tab')
        ->assertScript('document.activeElement?.getAttribute("data-testid") === "sidebar-brand"')
        ->assertVisible('[role="tooltip"]')
        ->keys('[data-testid="sidebar-brand"]', 'Escape')
        ->assertMissing('[role="tooltip"]')
        ->hover('[data-slot="sidebar-inset"]')
        ->hover('[data-testid="sidebar-brand"]')
        ->assertVisible('[role="tooltip"]')
        ->assertSee('Platformversie v1.4.0')
        ->screenshot(filename: 'sidebar-version-tooltip', fullPage: false);

    $page->click('button[data-sidebar="trigger"]')
        ->hover('[data-testid="sidebar-brand"]')
        ->assertVisible('[role="tooltip"]')
        ->assertSee('Platformversie v1.4.0')
        ->click('[data-test="sidebar-menu-button"]')
        ->assertScript('!document.querySelector("[role=menu]")?.textContent?.includes("Platformversie v1.4.0")')
        ->assertNoJavaScriptErrors();
});

test('the mobile user menu shows the version below logout without a logo tooltip', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);
    $this->actingAs($user);

    visit(route('dashboard'))
        ->on()->iPhone14Pro()
        ->inDarkMode()
        ->click('button[data-sidebar="trigger"]')
        ->assertMissing('[data-testid="sidebar-version"]')
        ->assertDontSee('Platformversie v1.4.0')
        ->click('[data-test="sidebar-menu-button"]')
        ->assertSee('Platformversie v1.4.0')
        ->assertScript('document.querySelector("[role=menu]")?.textContent?.trim().endsWith("Platformversie v1.4.0")')
        ->assertMissing('[role="tooltip"]')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->screenshot(filename: 'mobile-user-menu-version', fullPage: false)
        ->assertNoJavaScriptErrors();
});
