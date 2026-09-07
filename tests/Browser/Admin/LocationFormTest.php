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

test('location facilities can be edited and remain selected after saving', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $location = Location::factory()->create(['facilities' => ['parking' => 'available', 'power' => true, 'legacy' => ['Eigen pitruimte']]]);
    $this->actingAs($admin);

    visit(route('admin.locations.edit', $location, false))
        ->assertChecked('#facility-parking')
        ->assertChecked('input[name="facility-type-parking"][value="available"]')
        ->assertMissing('#facility-catering-type')
        ->assertSee('Eigen pitruimte')
        ->click('label:has(input[name="facility-type-parking"][value="paid"])')
        ->uncheck('#facility-power')
        ->check('#facility-catering')
        ->click('label:has(input[name="facility-type-catering"][value="on_site"])')
        ->check('#facility-wifi')
        ->click('label:has(input[name="facility-type-wifi"][value="public"])')
        ->check('#facility-charging')
        ->click('Wijzigingen opslaan')
        ->assertSee('Opgeslagen')
        ->assertChecked('input[name="facility-type-parking"][value="paid"]')
        ->assertNotChecked('#facility-power')
        ->assertNoJavaScriptErrors();

    expect($location->refresh()->facilities)->toMatchArray(['parking' => 'paid', 'power' => false, 'catering' => 'on_site', 'wifi' => 'public', 'charging' => true, 'legacy' => ['Eigen pitruimte']]);

    visit(route('locations.show', $location, false))
        ->assertSee('Betaald parkeren')
        ->assertSee('Catering op locatie')
        ->assertSee('Publieke wifi')
        ->assertSee('Oplaadmogelijkheid')
        ->assertDontSee('Stroomvoorziening')
        ->assertNoJavaScriptErrors();
});

test('typed facilities can be unchecked on mobile and saved as unavailable', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $location = Location::factory()->create(['facilities' => ['parking' => 'paid']]);
    $this->actingAs($admin);

    visit(route('admin.locations.edit', $location, false))
        ->on()->mobile()
        ->uncheck('#facility-parking')
        ->assertMissing('#facility-parking-type')
        ->check('#facility-parking')
        ->assertChecked('input[name="facility-type-parking"][value="paid"]')
        ->uncheck('#facility-parking')
        ->click('button[type=submit]')
        ->assertSee('Opgeslagen')
        ->assertNotChecked('#facility-parking')
        ->assertNoJavaScriptErrors();

    expect($location->refresh()->facilities)->toMatchArray(['parking' => 'none']);
});
