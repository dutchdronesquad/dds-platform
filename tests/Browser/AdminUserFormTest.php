<?php

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Vite;

beforeEach(function () {
    Vite::useHotFile(storage_path('framework/testing/vite.hot'));
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('the user form displays and changes the locale with an aligned desktop layout', function () {
    $admin = User::factory()->create(['locale' => 'en']);
    $admin->assignRole(Role::Admin->value);
    $managedUser = User::factory()->create(['locale' => 'en']);

    $this->actingAs($admin);

    visit("/dashboard/users/{$managedUser->id}/edit")
        ->on()->desktop()
        ->resize(1180, 900)
        ->assertNoJavaScriptErrors()
        ->assertSee('English')
        ->assertDontSee('Account actief')
        ->assertSee('Accountbeheer')
        ->assertSee('Account blokkeren')
        ->assertSee('Blokkeer eerst')
        ->assertScript(
            "(() => { const layout = document.querySelector('[data-testid=\"admin-form-layout\"]'); const content = document.querySelector('[data-testid=\"admin-form-content\"]'); const aside = document.querySelector('[data-testid=\"admin-form-aside\"]'); const locale = document.querySelector('#locale'); const name = document.querySelector('#name'); if (layout === null || content === null || aside === null || locale === null || name === null) return false; const columns = getComputedStyle(layout).gridTemplateColumns.split(' ').filter(Boolean); return columns.length === 2 && Math.abs(locale.getBoundingClientRect().left - name.getBoundingClientRect().left) < 1 && aside.getBoundingClientRect().left > content.getBoundingClientRect().right && document.documentElement.scrollWidth <= window.innerWidth; })()",
        )
        ->click('#locale')
        ->click('@locale-option-nl')
        ->assertScript(
            "document.querySelector('[data-testid=\"locale-value\"]')?.value === 'nl' && document.querySelector('#locale')?.textContent?.includes('Nederlands') && document.querySelector('[data-testid=\"admin-form-save-status\"]')?.dataset.state === 'dirty'",
        )
        ->assertNoJavaScriptErrors();
});
