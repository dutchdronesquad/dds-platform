<?php

use App\Enums\Role;
use App\Models\Event;
use App\Models\TrackDrawConnection;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Vite;

beforeEach(function () {
    config(['services.trackdraw.url' => 'https://trackdraw.app']);
    Vite::useHotFile(storage_path('framework/testing/vite.hot'));
    $this->seed(RolesAndPermissionsSeeder::class);
    Http::preventStrayRequests();
    Storage::fake('s3');
});

test('an editor can attach and remove a course using a saved connection without an API key input', function () {
    pest()->browser()->timeout(15000);
    $editor = User::factory()->create();
    $editor->assignRole(Role::Editor->value);
    $event = Event::factory()->create();
    TrackDrawConnection::factory()->create(['name' => 'DDS']);
    $connection = TrackDrawConnection::factory()->create(['name' => 'Private', 'api_key' => 'private-key']);
    $bytes = File::get(base_path('tests/Fixtures/trackdraw-snapshot.json'));
    Http::fake([
        'https://trackdraw.app/api/v1/projects/course-1/viewer-snapshot' => Http::response(['data' => json_decode($bytes, true)]),
        'https://trackdraw.app/api/v1/projects?*' => Http::sequence()
            ->push(['data' => [['id' => 'other', 'title' => 'Andere baan']], 'pagination' => ['has_more' => true, 'next_cursor' => 'page-two']])
            ->push(['data' => [['id' => 'course-1', 'title' => 'DDS testbaan']], 'pagination' => ['has_more' => false, 'next_cursor' => null]]),
    ]);
    $this->actingAs($editor);

    $page = visit(route('admin.events.edit', $event))
        ->click('#event-tab-track')
        ->assertScript('(() => { const tops = ["track-connection", "track-project", "track-default-view"].map(id => document.getElementById(id).getBoundingClientRect().top); return Math.max(...tops) - Math.min(...tops) < 2; })()')
        ->click('#track-connection')->click('[role=option]:has-text("Private")')
        ->assertSee('2 cloudprojecten beschikbaar.')
        ->click('#track-project')->click('[role=option]:has-text("DDS testbaan")')
        ->click('#track-default-view')->click('[role=option]:has-text("3D — perspectief")')
        ->assertSee('Nog niet opgeslagen')->press('Wijzigingen opslaan')
        ->assertSee('DDS testbaan')
        ->assertMissing('input[name=api_key]')
        ->assertPresent('.trackdraw-viewer canvas')
        ->assertSee('Opgeslagen')
        ->screenshot(filename: 'event-track-desktop')
        ->assertScript('document.querySelector("[data-testid=admin-form-save-status]").dataset.state', 'unchanged')
        ->assertNoJavaScriptErrors();
    expect($event->fresh())->track_draw_connection_id->toBe($connection->id)
        ->trackdraw_default_view->toBe('3d');
    Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer private-key'));
    $page->press('Loskoppelen');
    $page->assertSee('De track wordt losgekoppeld wanneer je het event opslaat.');
    $page->press('Wijzigingen opslaan');
    $page->assertMissing('.trackdraw-viewer canvas')->assertNoJavaScriptErrors();
});

afterEach(function () {
    pest()->browser()->timeout(5000);
});

test('a public event renders its saved course and switches between 2D and 3D', function (bool $mobile) {
    pest()->browser()->timeout(15000);
    Storage::disk('s3')->put('course.json', File::get(base_path('tests/Fixtures/trackdraw-snapshot.json')));
    $event = Event::factory()->published()->create(['trackdraw_snapshot_path' => 'course.json', 'trackdraw_title' => 'DDS testbaan', 'trackdraw_default_view' => '3d']);
    Http::fake(['*' => Http::failedConnection()]);

    $page = visit(route('events.show', $event->slug));
    $page = $mobile ? $page->on()->mobile() : $page->on()->desktop();
    $page->assertSee('DDS testbaan')
        ->assertPresent('.trackdraw-viewer canvas')
        ->assertSee('Bekijk wat je gaat vliegen.')
        ->assertMissing('.trackdraw-viewer button[aria-pressed]')
        ->assertScript('document.querySelector("[aria-label=Trackweergave]").getBoundingClientRect().bottom <= document.querySelector(".trackdraw-viewer").getBoundingClientRect().top')
        ->assertScript('document.querySelector(".dds-track-viewer button[aria-pressed=true]").textContent', '3D')
        ->assertScript('getComputedStyle(document.querySelector(".dds-track-viewer button[aria-pressed=true]")).minHeight', '32px')
        ->assertScript('getComputedStyle(document.querySelector(".trackdraw-viewer [data-viewer-gizmo]")).backgroundColor', 'rgb(23, 39, 46)')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->assertNoJavaScriptErrors();
    $page->click('2D')
        ->assertScript('document.querySelector(".dds-track-viewer button[aria-pressed=true]").textContent', '2D')
        ->assertPresent('.trackdraw-viewer canvas')->assertNoJavaScriptErrors()
        ->screenshotElement('.dds-track-viewer', filename: $mobile ? 'event-track-mobile-2d' : 'event-track-desktop-2d');
    $page->click('3D')
        ->assertScript('document.querySelector(".dds-track-viewer button[aria-pressed=true]").textContent', '3D')
        ->screenshotElement('.dds-track-viewer', filename: $mobile ? 'event-track-mobile-3d' : 'event-track-desktop-3d')
        ->assertNoJavaScriptErrors();
})->with(['mobile' => [true], 'desktop' => [false]]);

test('an administrator adds multiple named connections through Integrations and can rotate or remove one', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $this->actingAs($admin);
    $page = visit(route('admin.integrations.index'))
        ->screenshot(filename: 'integrations-overview')
        ->click('TrackDraw instellen')
        ->press('Koppeling toevoegen')
        ->type('#new-connection-name', 'DDS')
        ->type('#new-connection-key', 'central-test-key')
        ->press('Koppeling opslaan')
        ->assertSee('DDS')->assertMissing('[role="dialog"]')
        ->press('Koppeling toevoegen')
        ->assertScript('document.querySelector("#new-connection-key").value', '')
        ->type('#new-connection-name', 'Private')
        ->type('#new-connection-key', 'private-test-key')
        ->press('Koppeling opslaan')
        ->assertSee('Private')->assertMissing('[role="dialog"]');
    $page->screenshot(filename: 'trackdraw-integrations');
    $connection = TrackDrawConnection::query()->where('name', 'DDS')->sole();
    $page->click('button[aria-label="Bewerk DDS"]')
        ->assertScript('document.querySelector("#connection-'.$connection->id.'-key").value', '')
        ->type('#connection-'.$connection->id.'-key', 'rotated-key')
        ->press('Wijzigingen opslaan')->assertMissing('[role="dialog"]');
    expect($connection->fresh()->api_key)->toBe('rotated-key');
    $page->click('button[aria-label="Verwijder DDS"]')
        ->assertSee('Opgeslagen eventbanen blijven behouden.')
        ->press('Annuleren')->assertMissing('[role="dialog"]')
        ->assertSee('DDS')
        ->click('button[aria-label="Verwijder DDS"]')
        ->press('button:has-text("Definitief verwijderen")')
        ->assertMissing('[role="dialog"]')
        ->assertDontSee('DDS')->assertSee('Private')->assertNoJavaScriptErrors();
    expect(TrackDrawConnection::query()->sole()->api_key)->toBe('private-test-key');
});

test('an invalid saved snapshot shows a recoverable error instead of mounting the viewer', function () {
    Storage::disk('s3')->put('invalid.json', '{"schema":"unknown"}');
    $event = Event::factory()->published()->create(['trackdraw_snapshot_path' => 'invalid.json', 'trackdraw_title' => 'Invalid course']);
    visit(route('events.show', $event->slug))
        ->assertSee('De baan kan momenteel niet worden weergegeven.')
        ->assertMissing('.trackdraw-viewer canvas')
        ->assertNoJavaScriptErrors();
});

test('the connections table and edit dialog fit on mobile and restore keyboard focus', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    TrackDrawConnection::factory()->create(['name' => 'DDS wedstrijdaccount']);
    $this->actingAs($admin);
    $page = visit(route('admin.integrations.trackdraw.index'))->on()->mobile()
        ->assertSee('DDS wedstrijdaccount')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->screenshot(filename: 'trackdraw-integrations-mobile')
        ->click('button[aria-label="Bewerk DDS wedstrijdaccount"]')
        ->assertSee('Koppeling bewerken')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->screenshot(filename: 'trackdraw-integration-dialog-mobile')
        ->press('Annuleren')->assertMissing('[role="dialog"]')
        ->assertScript('document.activeElement.getAttribute("aria-label")', 'Bewerk DDS wedstrijdaccount')
        ->assertNoJavaScriptErrors();
});

test('event tabs preserve edits and place navigation directly below the header actions', function () {
    $editor = User::factory()->create();
    $editor->assignRole(Role::Admin->value);
    $event = Event::factory()->create(['registration_enabled' => false]);
    $this->actingAs($editor);

    $page = visit(route('admin.events.edit', $event))->on()->desktop()
        ->fill('#title', 'Gewijzigde eventtitel')
        ->click('#event-tab-registration')
        ->fill('#capacity', '24')
        ->click('#event-tab-page')
        ->fill('#content', 'Nieuwe praktische informatie')
        ->click('#event-tab-track')
        ->assertSee('Opgeslagen track')
        ->assertSee('Nog niet opgeslagen')
        ->click('#event-tab-general')
        ->assertScript('document.querySelector("#title").value', 'Gewijzigde eventtitel')
        ->assertScript('document.querySelectorAll("h1").length', 1)
        ->assertScript('(() => { const heading = document.querySelector("h1"); const tabs = document.querySelector("[role=tablist]"); const save = document.querySelector("button[type=submit]"); return heading.getBoundingClientRect().top < tabs.getBoundingClientRect().top && save.getBoundingClientRect().bottom <= tabs.getBoundingClientRect().top; })()')
        ->screenshot(filename: 'event-tabs-desktop')
        ->press('Wijzigingen opslaan')
        ->assertSee('Opgeslagen')
        ->assertNoJavaScriptErrors();
    expect($event->fresh())->title->toBe('Gewijzigde eventtitel')
        ->capacity->toBe(24)->content->toBe('Nieuwe praktische informatie');

    $page->resize(390, 844)->click('#event-tab-track')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->screenshot(filename: 'event-tabs-mobile')
        ->assertNoJavaScriptErrors();
});

test('switching accounts clears the project and cloud errors can be retried', function () {
    $editor = User::factory()->create();
    $editor->assignRole(Role::Editor->value);
    $event = Event::factory()->create();
    TrackDrawConnection::factory()->create(['name' => 'DDS']);
    TrackDrawConnection::factory()->create(['name' => 'Private']);
    Http::fake(['https://trackdraw.app/api/v1/projects?*' => Http::sequence()
        ->push(['data' => [['id' => 'course', 'title' => 'DDS baan']], 'pagination' => ['has_more' => false, 'next_cursor' => null]])
        ->push([], 401)
        ->push(['data' => [], 'pagination' => ['has_more' => false, 'next_cursor' => null]])]);
    $this->actingAs($editor);

    visit(route('admin.events.edit', $event))
        ->click('#event-tab-track')
        ->click('#track-connection')->click('[role=option]:has-text("DDS")')
        ->assertSee('1 cloudproject beschikbaar.')
        ->click('#track-project')->click('[role=option]:has-text("DDS baan")')
        ->click('#track-connection')->click('[role=option]:has-text("Private")')
        ->assertSee('De cloudprojecten konden niet worden opgehaald.')
        ->assertScript('document.querySelector("#track-project").textContent.includes("DDS baan")', false)
        ->press('Opnieuw proberen')
        ->assertSee('Geen cloudprojecten gevonden.')
        ->assertNoJavaScriptErrors();
    Http::assertSentCount(3);
    expect($event->fresh()->trackdraw_project_id)->toBeNull();
});

test('saving from another event tab reveals an invalid required field without losing edits', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $event = Event::factory()->create(['registration_enabled' => false]);
    $this->actingAs($admin);

    visit(route('admin.events.edit', $event))
        ->fill('#title', '')
        ->click('#event-tab-page')
        ->fill('#content', 'Bewaar deze invoer')
        ->press('Wijzigingen opslaan')
        ->assertAttribute('#event-tab-general', 'aria-selected', 'true')
        ->assertScript('document.activeElement.id', 'title')
        ->click('#event-tab-page')
        ->assertScript('document.querySelector("#content").value', 'Bewaar deze invoer')
        ->assertNoJavaScriptErrors();
});
