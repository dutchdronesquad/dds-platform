<?php

use App\Enums\Role;
use App\Models\Event;
use App\Models\TrackDrawConnection;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    config(['services.trackdraw.url' => 'https://trackdraw.app']);
    Storage::fake('s3');
    Http::preventStrayRequests();
});

/**
 * @param  array<string, mixed>  $track
 * @return array<string, mixed>
 */
function eventWithTrackPayload(Event $event, array $track): array
{
    return [
        'title' => 'Nieuwe eventtitel',
        'location_id' => $event->location_id,
        'starts_at' => '2026-12-01T10:00:00+01:00',
        'type' => 'training',
        'registration_enabled' => false,
        'registration_closed_manually' => false,
        'registration_full' => false,
        'registration_waitlist_enabled' => false,
        ...$track,
    ];
}

test('saving event settings also attaches the selected track and replaces its previous snapshot', function () {
    $editor = User::factory()->create();
    $editor->assignRole(Role::Editor->value);
    $event = Event::factory()->create(['trackdraw_snapshot_path' => 'old.json']);
    Storage::disk('s3')->put('old.json', 'old');
    $connection = TrackDrawConnection::factory()->create(['api_key' => 'selected-key']);
    Http::fake(['https://trackdraw.app/api/v1/projects/course-1/viewer-snapshot' => Http::response(['data' => json_decode(File::get(base_path('tests/Fixtures/trackdraw-snapshot.json')), true)])]);

    $this->actingAs($editor)->put(route('admin.events.update', $event), eventWithTrackPayload($event, [
        'track_action' => 'replace', 'track_connection_id' => $connection->id, 'track_project_id' => 'course-1',
    ]))->assertSessionHasNoErrors()->assertRedirect(route('admin.events.edit', $event));

    $event->refresh();
    expect($event)->title->toBe('Nieuwe eventtitel')->trackdraw_project_id->toBe('course-1')
        ->track_draw_connection_id->toBe($connection->id)->trackdraw_title->toBe('DDS testbaan');
    Storage::disk('s3')->assertExists($event->trackdraw_snapshot_path);
    Storage::disk('s3')->assertMissing('old.json');
    Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer selected-key'));
});

test('a failed track download prevents partial saving of event settings', function () {
    $editor = User::factory()->create();
    $editor->assignRole(Role::Editor->value);
    $event = Event::factory()->create(['title' => 'Bestaande titel', 'trackdraw_snapshot_path' => 'old.json']);
    $connection = TrackDrawConnection::factory()->create();
    Storage::disk('s3')->put('old.json', 'old');
    Http::fake(['https://trackdraw.app/api/v1/projects/course-1/viewer-snapshot' => Http::failedConnection()]);

    $this->actingAs($editor)->put(route('admin.events.update', $event), eventWithTrackPayload($event, [
        'track_action' => 'replace', 'track_connection_id' => $connection->id, 'track_project_id' => 'course-1',
    ]))->assertSessionHasErrors('track_project_id');

    expect($event->fresh())->title->toBe('Bestaande titel')->trackdraw_snapshot_path->toBe('old.json');
    Storage::disk('s3')->assertCount('/', 1);
});

test('normal event saves keep the track without contacting TrackDraw and removal cleans up on save', function () {
    $editor = User::factory()->create();
    $editor->assignRole(Role::Editor->value);
    $event = Event::factory()->create(['trackdraw_snapshot_path' => 'old.json', 'trackdraw_title' => 'Baan']);
    Storage::disk('s3')->put('old.json', 'old');

    $this->actingAs($editor)->put(route('admin.events.update', $event), eventWithTrackPayload($event, ['track_action' => 'keep']))->assertSessionHasNoErrors();
    expect($event->fresh()->trackdraw_snapshot_path)->toBe('old.json');
    $this->put(route('admin.events.update', $event), eventWithTrackPayload($event, ['track_action' => 'remove']))->assertSessionHasNoErrors();
    expect($event->fresh())->trackdraw_snapshot_path->toBeNull()->trackdraw_title->toBeNull();
    Storage::disk('s3')->assertMissing('old.json');
    Http::assertNothingSent();
});

test('an incomplete track selection rejects the event save', function () {
    $editor = User::factory()->create();
    $editor->assignRole(Role::Editor->value);
    $event = Event::factory()->create(['title' => 'Bestaande titel']);

    $this->actingAs($editor)->put(route('admin.events.update', $event), eventWithTrackPayload($event, ['track_action' => 'replace']))
        ->assertSessionHasErrors(['track_connection_id', 'track_project_id']);
    expect($event->fresh()->title)->toBe('Bestaande titel');
    Http::assertNothingSent();
});

test('local development can store and serve a private track without S3 credentials', function () {
    config(['services.trackdraw.disk' => 'local']);
    Storage::fake('local');
    $editor = User::factory()->create();
    $editor->assignRole(Role::Editor->value);
    $event = Event::factory()->create();
    $connection = TrackDrawConnection::factory()->create();
    Http::fake(['https://trackdraw.app/api/v1/projects/course-1/viewer-snapshot' => Http::response(['data' => json_decode(File::get(base_path('tests/Fixtures/trackdraw-snapshot.json')), true)])]);

    $this->actingAs($editor)->put(route('admin.events.update', $event), eventWithTrackPayload($event, [
        'track_action' => 'replace', 'track_connection_id' => $connection->id, 'track_project_id' => 'course-1',
    ]))->assertSessionHasNoErrors();
    $event->refresh();
    Storage::disk('local')->assertExists($event->trackdraw_snapshot_path);
    Storage::disk('s3')->assertCount('/', 0);
    expect(Storage::disk('local')->getVisibility($event->trackdraw_snapshot_path))->toBe('private');
    $this->get(route('events.track', $event->slug))->assertOk()->assertHeader('Cache-Control', 'no-store, private');
    auth()->logout();
    $this->get(route('events.track', $event->slug))->assertNotFound();
});

test('an editor can change the initial viewer mode without downloading the track again', function () {
    $editor = User::factory()->create();
    $editor->assignRole(Role::Editor->value);
    $event = Event::factory()->published()->create(['trackdraw_title' => 'Baan', 'trackdraw_snapshot_path' => 'saved.json']);
    expect($event->fresh()->trackdraw_default_view)->toBe('2d');

    $this->actingAs($editor)->put(route('admin.events.update', $event), eventWithTrackPayload($event, [
        'track_action' => 'keep', 'trackdraw_default_view' => '3d',
    ]))->assertSessionHasNoErrors();
    expect($event->fresh())->trackdraw_default_view->toBe('3d')->trackdraw_snapshot_path->toBe('saved.json');
    $this->get(route('admin.events.edit', $event))->assertInertia(fn (AssertableInertia $page) => $page->where('event.track.defaultView', '3d'));
    $this->get(route('events.show', $event->slug))->assertInertia(fn (AssertableInertia $page) => $page->where('event.trackDefaultView', '3d'));
    $this->put(route('admin.events.update', $event), eventWithTrackPayload($event, [
        'trackdraw_default_view' => 'invalid',
    ]))->assertSessionHasErrors('trackdraw_default_view');
    expect($event->fresh()->trackdraw_default_view)->toBe('3d');
    Http::assertNothingSent();
});
