<?php

use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Enums\Role;
use App\Models\Event;
use App\Models\Season;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Vite;

beforeEach(function () {
    Vite::useHotFile(storage_path('framework/testing/vite.hot'));
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('admins can select multiple event facets without closing the menu', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    Event::factory()->create([
        'title' => 'Concepttraining',
        'status' => EventStatus::Draft,
    ]);
    Event::factory()->published()->create([
        'title' => 'Gepubliceerde race',
    ]);

    $this->actingAs($admin);

    $draftOption = 'internal:role=menuitemcheckbox[name="Concept"s]';
    $publishedOption = 'internal:role=menuitemcheckbox[name="Gepubliceerd"s]';

    visit('/dashboard/events')
        ->on()->desktop()
        ->assertNoJavaScriptErrors()
        ->wait(1)
        ->click('button[aria-label="Filter op status"]')
        ->assertVisible($draftOption)
        ->assertAriaAttribute($draftOption, 'checked', 'false')
        ->click($draftOption)
        ->assertAriaAttribute($draftOption, 'checked', 'true')
        ->assertVisible($publishedOption)
        ->assertSee('Concepttraining')
        ->assertDontSee('Gepubliceerde race')
        ->click($publishedOption)
        ->assertAriaAttribute($publishedOption, 'checked', 'true')
        ->assertSee('Concepttraining')
        ->assertSee('Gepubliceerde race')
        ->assertScript(
            "[...new URLSearchParams(window.location.search).values()].filter((value) => ['draft', 'published'].includes(value)).sort()",
            ['draft', 'published'],
        )
        ->assertNoJavaScriptErrors();
});

test('event index separates type and season context without exposing slugs', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $season = Season::factory()->create(['name' => 'Wintercompetitie']);

    Event::factory()->create([
        'season_id' => $season->id,
        'slug' => 'verborgen-race-slug',
        'title' => 'Finalerace',
        'type' => EventType::Race,
    ]);
    Event::factory()->create([
        'season_id' => null,
        'slug' => 'verborgen-training-slug',
        'title' => 'Losse training',
        'type' => EventType::Training,
    ]);

    $this->actingAs($admin);

    visit('/dashboard/events')
        ->on()->desktop()
        ->resize(1440, 1000)
        ->assertNoJavaScriptErrors()
        ->assertSee('Type')
        ->assertSee('Seizoen')
        ->assertSee('Race')
        ->assertSee('Training')
        ->assertSee('Wintercompetitie')
        ->assertSee('Los event')
        ->assertDontSee('verborgen-race-slug')
        ->assertDontSee('verborgen-training-slug')
        ->assertScript(
            "(() => { const headings = Array.from(document.querySelectorAll('thead th')).map((heading) => heading.textContent.trim()); return ['Event', 'Start', 'Locatie', 'Type', 'Seizoen', 'Status'].every((heading) => headings.includes(heading)) && document.documentElement.scrollWidth <= window.innerWidth; })()",
        )
        ->assertNoJavaScriptErrors();
});
