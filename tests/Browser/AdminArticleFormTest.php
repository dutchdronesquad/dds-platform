<?php

use App\Enums\Role;
use App\Models\Article;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Vite;

beforeEach(function () {
    Vite::useHotFile(storage_path('framework/testing/vite.hot'));
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('the article form clearly distinguishes a concept from publication', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    $this->actingAs($admin);

    visit('/dashboard/articles/create')
        ->on()->desktop()
        ->assertNoJavaScriptErrors()
        ->assertSee('Opslaan als concept')
        ->assertSee('Dit artikel wordt als concept opgeslagen en is alleen via de voorbeeldweergave zichtbaar.')
        ->click('#status')
        ->click('internal:role=option[name="Gepubliceerd"s]')
        ->assertSee('Artikel publiceren')
        ->assertSee('Dit artikel wordt publiek. Zonder publicatiedatum gebeurt dat meteen; met een toekomstige datum pas op dat moment.')
        ->assertNoJavaScriptErrors();
});

test('the article form previews markdown while editing', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    $this->actingAs($admin);

    visit('/dashboard/articles/create')
        ->on()->desktop()
        ->fill('#content', "## Wedstrijdverslag\n\nEen **spannende** finale.")
        ->click('button[aria-controls="content-preview"]')
        ->assertScript(
            "document.querySelector('#content-preview h2')?.textContent === 'Wedstrijdverslag' && document.querySelector('#content-preview strong')?.textContent === 'spannende'",
        )
        ->assertNoJavaScriptErrors();
});

test('the article publication date can be set to the current time', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    $this->actingAs($admin);

    visit('/dashboard/articles/create')
        ->on()->desktop()
        ->click('#published_at')
        ->click('internal:role=button[name="Nu"s]')
        ->assertScript(
            "(() => { const value = document.querySelector('input[name=\"published_at\"]')?.value; if (!value) return false; return value.endsWith('Z') && Math.abs(new Date(value).getTime() - Date.now()) < 120000; })()",
        )
        ->assertNoJavaScriptErrors();
});

test('a saved draft article has a protected preview in a new tab', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $article = Article::factory()->create(['title' => 'Conceptartikel']);

    $this->actingAs($admin);

    visit("/dashboard/articles/{$article->id}/edit")
        ->on()->desktop()
        ->assertNoJavaScriptErrors()
        ->assertScript(
            "(() => { const previewLink = document.querySelector('a[href$=\"/preview\"][target=\"_blank\"]'); if (previewLink === null) return false; const rel = new Set((previewLink.getAttribute('rel') ?? '').split(/\\s+/)); return rel.has('noopener') && rel.has('noreferrer') && previewLink.dataset.sidebarAction === 'preview' && previewLink.textContent?.includes('Voorbeeld bekijken') === true; })()",
        );

    visit("/dashboard/articles/{$article->id}/preview")
        ->on()->desktop()
        ->assertNoJavaScriptErrors()
        ->assertSee('Voorbeeldweergave · dit artikel is nog niet gepubliceerd')
        ->assertSee('Nog niet gepubliceerd');
});
