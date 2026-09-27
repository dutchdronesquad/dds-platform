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
    Vite::useHotFile(storage_path('framework/testing/vite.hot'));
    $this->seed(RolesAndPermissionsSeeder::class);
    Http::preventStrayRequests();
    Storage::fake('s3');
});

test('an editor can attach and remove a course using a saved connection without an API key input', function () {
    $editor = User::factory()->create();
    $editor->assignRole(Role::Editor->value);
    $event = Event::factory()->create();
    TrackDrawConnection::factory()->create(['name' => 'DDS']);
    $connection = TrackDrawConnection::factory()->create(['name' => 'Private', 'api_key' => 'private-key']);
    $bytes = File::get(base_path('tests/Fixtures/trackdraw-snapshot.json'));
    Http::fake(['https://trackdraw.app/api/v1/projects/course-1/viewer-snapshot' => Http::response(['data' => json_decode($bytes, true)])]);
    $this->actingAs($editor);

    $page = visit(route('admin.events.edit', $event))
        ->click('#track-connection')->click('[role=option]:has-text("Private")')
        ->type('project_id', 'course-1')
        ->press('Baan koppelen')
        ->assertSee('DDS testbaan')
        ->assertMissing('input[name=api_key]')
        ->assertPresent('.trackdraw-viewer canvas')
        ->assertNoJavaScriptErrors();
    expect($event->fresh()->track_draw_connection_id)->toBe($connection->id);
    Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer private-key'));
    $page->press('Baan loskoppelen')->assertDontSee('DDS testbaan')->assertNoJavaScriptErrors();
});

test('a public event renders its saved course on mobile and switches between 2D and 3D', function () {
    Storage::disk('s3')->put('course.json', File::get(base_path('tests/Fixtures/trackdraw-snapshot.json')));
    $event = Event::factory()->published()->create(['trackdraw_snapshot_path' => 'course.json', 'trackdraw_title' => 'DDS testbaan']);
    Http::fake(['*' => Http::failedConnection()]);

    $page = visit(route('events.show', $event->slug))->on()->mobile()
        ->assertSee('DDS testbaan')
        ->assertPresent('.trackdraw-viewer canvas')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->assertNoJavaScriptErrors();
    $page->click('3D')->assertNoJavaScriptErrors()->click('2D')
        ->assertPresent('.trackdraw-viewer canvas')->assertNoJavaScriptErrors()
        ->screenshotElement('.trackdraw-viewer', filename: 'event-track-mobile');
});

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
