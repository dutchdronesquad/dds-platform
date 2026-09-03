<?php

use App\Enums\Role;
use App\Models\Location;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Vite;

beforeEach(function () {
    Vite::useHotFile(storage_path('framework/testing/vite.hot'));
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('location forms offer one Dutch description with a Markdown preview', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    $this->actingAs($admin);

    visit('/dashboard/locations/create')
        ->on()->desktop()
        ->resize(1280, 900)
        ->assertNoJavaScriptErrors()
        ->assertSee('Omschrijving')
        ->assertMissing('#description_en')
        ->assertPresent('#description_nl')
        ->fill('#description_nl', "## Praktische informatie\n\n- Eigen parkeerplaats\n- Stroom aanwezig")
        ->click('button[aria-controls="description_nl-preview"]')
        ->assertSee('Praktische informatie')
        ->assertScript(
            "document.querySelector('#description_nl-preview h2')?.textContent === 'Praktische informatie' && document.querySelector('#description_nl-preview li')?.textContent === 'Eigen parkeerplaats'",
        )
        ->assertNoJavaScriptErrors();
});

test('location forms carry legacy English field copy into the Dutch editor', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $location = Location::factory()->create([
        'description' => ['en' => 'Nederlandse tekst uit het oude verplichte veld.'],
    ]);

    $this->actingAs($admin);

    visit(route('admin.locations.edit', $location, false))
        ->on()->desktop()
        ->assertNoJavaScriptErrors()
        ->assertMissing('#description_en')
        ->assertValue(
            '#description_nl',
            'Nederlandse tekst uit het oude verplichte veld.',
        );
});
