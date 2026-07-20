<?php

use App\Enums\EventRegistrationStatus;
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

test('admin dashboard prioritizes open points quick actions and recent changes', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $actor = User::factory()->create(['name' => 'Eventbeheerder']);
    $season = Season::factory()->create([
        'name' => 'Wintercompetitie',
        'updated_by' => $actor->id,
    ]);

    Event::factory()->create([
        'content' => null,
        'title' => 'Eerstvolgende training',
        'starts_at' => now()->addDay(),
        'updated_by' => $actor->id,
    ]);
    Event::factory()->published()->create([
        'title' => 'Race met gesloten registratie',
        'starts_at' => now()->addDays(2),
        'registration_status' => EventRegistrationStatus::Closed,
        'updated_by' => $actor->id,
    ]);
    Event::factory()->published()->create([
        'season_id' => $season->id,
        'title' => 'Race met open registratie',
        'starts_at' => now()->addDays(3),
        'registration_deadline_at' => now()->subHour(),
        'registration_status' => EventRegistrationStatus::Open,
        'updated_by' => $actor->id,
    ]);

    $this->actingAs($admin);

    visit('/dashboard')
        ->on()->desktop()
        ->resize(1440, 1200)
        ->assertNoJavaScriptErrors()
        ->assertSee('Open punten')
        ->assertSee('Snel aan de slag')
        ->assertSee('Recente wijzigingen')
        ->assertSee('Eerstvolgende training')
        ->assertSee('Registratie gesloten')
        ->assertSee('Eventbeheerder')
        ->assertSee('Nieuw event')
        ->assertSee('Nieuw seizoen')
        ->assertSee('Gebruikers beheren')
        ->assertDontSee('Open beheer')
        ->assertScript(
            "(() => { const draftStat = document.querySelector('[data-testid=\"dashboard-stat-drafts\"]'); const upcomingStat = document.querySelector('[data-testid=\"dashboard-stat-upcoming\"]'); const recentStat = document.querySelector('[data-testid=\"dashboard-stat-recent\"]'); const quickActions = Array.from(document.querySelectorAll('[data-testid=\"quick-action\"]')).map((link) => new URL(link.href).pathname).sort(); return draftStat?.textContent.includes('1') && upcomingStat?.textContent.includes('3') && recentStat?.textContent.includes('4') && quickActions.join('|') === ['/dashboard/events/create', '/dashboard/seasons/create', '/dashboard/users'].sort().join('|') && document.querySelectorAll('[data-testid=\"recent-change\"]').length === 4 && document.querySelector('[data-testid=\"next-event\"]')?.textContent.includes('Eerstvolgende training') && document.documentElement.scrollWidth <= window.innerWidth; })()",
        )
        ->assertScript(
            "(() => { const hasValue = (testId, value) => Array.from(new URL(document.querySelector('[data-testid=\"' + testId + '\"]').href).searchParams.values()).includes(value); return hasValue('open-point-closed-registration', 'closed_registration') && hasValue('open-point-expired-registration', 'expired_registration') && hasValue('open-point-without-content', 'without_content') && hasValue('open-point-without-cover', 'without_cover') && hasValue('open-point-without-season', 'without_season'); })()",
        )
        ->click('[data-testid="open-point-closed-registration"]')
        ->assertPathIs('/dashboard/events')
        ->assertSee('Race met gesloten registratie')
        ->assertDontSee('Race met open registratie')
        ->assertScript(
            "(() => Array.from(new URL(window.location.href).searchParams.entries()).some(([key, value]) => key.startsWith('situation') && value === 'closed_registration') && document.querySelector('button[aria-label=\"Situatiefilter: Registratie gesloten\"]') !== null)()",
        )
        ->assertNoJavaScriptErrors();
});
