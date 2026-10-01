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

test('event index shows title with slug, type with season label, and keeps rows to two compact lines', function () {
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
        ->resize(1280, 800)
        ->assertNoJavaScriptErrors()
        ->assertSee('Race')
        ->assertSee('Training')
        ->assertSee('Wintercompetitie')
        ->assertDontSee('Los event')
        ->assertSee('/events/verborgen-race-slug')
        ->assertSee('/events/verborgen-training-slug')
        // Event column: title on line one, public path on line two.
        ->assertScript(
            "(() => { const finalRow = Array.from(document.querySelectorAll('tbody tr')).find((row) => row.textContent.includes('Finalerace')); const wrapper = finalRow?.querySelector('td')?.firstElementChild; const lines = Array.from(wrapper?.children ?? []).filter((line) => line.offsetParent !== null); const slug = lines.find((line) => line.textContent.includes('/events/verborgen-race-slug')); return lines.length === 2 && lines[0].textContent.includes('Finalerace') && !!slug && slug.getBoundingClientRect().height < 20 && lines[0].getBoundingClientRect().height < 24 && wrapper.getBoundingClientRect().height < 48; })()",
        )
        // Type column holds type plus the season label; planning and status keep two lines each; no row exceeds two lines.
        ->assertScript(
            "(() => { const headings = Array.from(document.querySelectorAll('thead th')).map((heading) => heading.textContent.trim()); const finalRow = Array.from(document.querySelectorAll('tbody tr')).find((row) => row.textContent.includes('Finalerace')); const cells = finalRow?.querySelectorAll('td'); const trainingRow = Array.from(document.querySelectorAll('tbody tr')).find((row) => row.textContent.includes('Losse training')); return ['Event', 'Type', 'Planning', 'Status'].every((heading) => headings.includes(heading)) && !['Start', 'Locatie', 'Seizoen'].some((heading) => headings.includes(heading)) && cells?.length === 6 && cells[1]?.textContent.includes('Race') && cells[1]?.querySelector('[data-slot=badge]')?.textContent.includes('Wintercompetitie') && !cells[0]?.innerText.includes('Wintercompetitie') && !cells[2]?.textContent.includes('Wintercompetitie') && cells[2]?.querySelectorAll('p').length === 2 && cells[3]?.querySelectorAll('p').length === 1 && trainingRow?.querySelectorAll('td')[1]?.querySelector('[data-slot=badge]') === null && finalRow.getBoundingClientRect().height < 90 && document.documentElement.scrollWidth <= window.innerWidth; })()",
        )
        ->assertNoJavaScriptErrors();
});
