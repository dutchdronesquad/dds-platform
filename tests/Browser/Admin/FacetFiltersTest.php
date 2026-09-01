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

test('event index groups type and season with its title and keeps planning compact', function () {
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
        ->assertSee('Planning')
        ->assertSee('Status')
        ->assertSee('Race')
        ->assertSee('Training')
        ->assertSee('Wintercompetitie')
        ->assertDontSee('Los event')
        ->assertDontSee('verborgen-race-slug')
        ->assertDontSee('verborgen-training-slug')
        ->assertScript(
            "(() => { const headings = Array.from(document.querySelectorAll('thead th')).map((heading) => heading.textContent.trim()); const finalRow = Array.from(document.querySelectorAll('tbody tr')).find((row) => row.textContent.includes('Finalerace')); const cells = finalRow?.querySelectorAll('td'); return ['Event', 'Planning', 'Status', 'Bijgewerkt'].every((heading) => headings.includes(heading)) && !['Start', 'Locatie', 'Type', 'Seizoen'].some((heading) => headings.includes(heading)) && cells?.length === 5 && cells[0]?.textContent.includes('Race') && cells[0]?.textContent.includes('Wintercompetitie') && !cells[1]?.textContent.includes('Wintercompetitie') && cells[1]?.querySelectorAll('p').length === 2 && !cells[2]?.textContent.includes('Race') && document.documentElement.scrollWidth <= window.innerWidth; })()",
        )
        ->assertNoJavaScriptErrors();
});
