<?php

use App\Enums\Role;
use App\Models\Event;
use App\Models\TrackDrawConnection;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('s3');
});

function fakeTrackSnapshot(): void
{
    $snapshot = json_decode(File::get(base_path('tests/Fixtures/trackdraw-snapshot.json')), true);
    $snapshot['source'] = ['project_id' => 'private-project'];
    Http::fake(['https://trackdraw.app/api/v1/projects/course-1/viewer-snapshot' => Http::response(['data' => $snapshot])]);
}

test('an editor stores the snapshot in object storage using the selected connection and can refresh or detach it', function () {
    $editor = User::factory()->create();
    $editor->assignRole(Role::Editor->value);
    $event = Event::factory()->published()->create();
    TrackDrawConnection::factory()->create();
    fakeTrackSnapshot();
    $this->actingAs($editor)->put(route('admin.events.track.update', $event), ['connection_id' => 1, 'project_id' => 'course-1'])
        ->assertSessionHasNoErrors()->assertRedirect();
    $event->refresh();
    $path = $event->trackdraw_snapshot_path;
    expect($event->trackdraw_title)->toBe('DDS testbaan')
        ->and($path)->toStartWith('event-tracks/')->toEndWith('.json')
        ->and($event->toArray())->not->toHaveKey('trackdraw_snapshot_path');
    expect(json_decode(Storage::disk('s3')->get($path), true))->toBe(json_decode(File::get(base_path('tests/Fixtures/trackdraw-snapshot.json')), true))
        ->and(Storage::disk('s3')->getVisibility($path))->toBe('private');
    Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer test-trackdraw-key'));
    $this->get(route('admin.events.edit', $event))->assertInertia(fn (Assert $page) => $page
        ->has('event.track.connections', 1)->where('event.track.connectionId', 1)->where('event.track.title', 'DDS testbaan')->missing('event.track.apiKey'));
    $this->put(route('admin.events.track.update', $event), ['connection_id' => 1, 'project_id' => 'course-1'])->assertSessionHasNoErrors();
    Storage::disk('s3')->assertMissing($path);
    $replacement = $event->fresh()->trackdraw_snapshot_path;
    Storage::disk('s3')->assertExists($replacement);
    $this->delete(route('admin.events.track.destroy', $event))->assertRedirect();
    Storage::disk('s3')->assertMissing($replacement);
    expect($event->fresh()->trackdraw_snapshot_path)->toBeNull()
        ->and(TrackDrawConnection::query()->find(1)?->api_key)->toBe('test-trackdraw-key');
});

test('failed or invalid downloads preserve the previous course', function (string $failure) {
    $editor = User::factory()->create();
    $editor->assignRole(Role::Editor->value);
    TrackDrawConnection::factory()->create();
    $event = Event::factory()->create(['trackdraw_snapshot_path' => 'old.json', 'trackdraw_project_id' => 'original']);
    Storage::disk('s3')->put('old.json', 'existing-course');
    $snapshot = json_decode(File::get(base_path('tests/Fixtures/trackdraw-snapshot.json')), true);
    if ($failure === 'title') {
        $snapshot['design']['title'] = '';
    }
    if ($failure === 'schema') {
        $snapshot['schema'] = 'unknown';
    }
    if ($failure === 'shapes') {
        $snapshot['design']['shapes'] = 'invalid';
    }
    $body = $failure === 'invalid json' ? 'invalid' : json_encode(['data' => $snapshot], JSON_THROW_ON_ERROR);
    if ($failure === 'oversized') {
        $body = str_repeat(' ', 4_000_001);
    }
    Http::fake(['*' => $failure === 'connection' ? Http::failedConnection() : Http::response($body, $failure === 'credentials' ? 401 : 200, [
        'Content-Type' => $failure === 'content type' ? 'text/html' : 'application/json',
    ])]);
    $this->actingAs($editor)->put(route('admin.events.track.update', $event), ['connection_id' => 1, 'project_id' => 'course-1'])
        ->assertSessionHasErrors('project_id');
    expect($event->fresh()->trackdraw_snapshot_path)->toBe('old.json')
        ->and($event->fresh()->trackdraw_project_id)->toBe('original')
        ->and(Storage::disk('s3')->get('old.json'))->toBe('existing-course');
    Storage::disk('s3')->assertCount('/', 1);
})->with(['credentials', 'connection', 'content type', 'schema', 'title', 'shapes', 'invalid json', 'oversized']);

test('failed object storage writes never replace the event attachment', function () {
    $editor = User::factory()->create();
    $editor->assignRole(Role::Editor->value);
    TrackDrawConnection::factory()->create();
    $event = Event::factory()->create(['trackdraw_snapshot_path' => 'old.json']);
    fakeTrackSnapshot();
    $disk = Mockery::mock(FilesystemAdapter::class);
    $disk->shouldReceive('put')->once()->andReturnFalse();
    Storage::shouldReceive('disk')->with('s3')->andReturn($disk);
    $this->actingAs($editor)->put(route('admin.events.track.update', $event), ['connection_id' => 1, 'project_id' => 'course-1'])
        ->assertSessionHasErrors('project_id');
    expect($event->fresh()->trackdraw_snapshot_path)->toBe('old.json');
});

test('track management rejects unauthorized users, invalid project paths and unknown connections', function () {
    $event = Event::factory()->create();
    $this->put(route('admin.events.track.update', $event))->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create())->put(route('admin.events.track.update', $event))->assertForbidden();
    $this->delete(route('admin.events.track.destroy', $event))->assertForbidden();
    $editor = User::factory()->create();
    $editor->assignRole(Role::Editor->value);
    $this->actingAs($editor)->put(route('admin.events.track.update', $event), ['connection_id' => 1, 'project_id' => '../../private'])
        ->assertSessionHasErrors('project_id');
    $this->put(route('admin.events.track.update', $event), ['connection_id' => 999, 'project_id' => 'course-1'])->assertSessionHasErrors('connection_id');
    Http::assertNothingSent();
    Storage::disk('s3')->assertDirectoryEmpty('/');
});

test('public snapshots require event visibility while editors can preview drafts', function () {
    Storage::disk('s3')->put('course.json', 'course-bytes');
    $event = Event::factory()->create(['trackdraw_snapshot_path' => 'course.json', 'trackdraw_title' => 'Course']);
    $this->get(route('events.track', $event->slug))->assertNotFound();
    $editor = User::factory()->create();
    $editor->assignRole(Role::Editor->value);
    $this->actingAs($editor)->get(route('events.track', $event->slug))->assertOk()->assertStreamedContent('course-bytes');
    $public = Event::factory()->published()->create(['trackdraw_snapshot_path' => 'course.json', 'trackdraw_title' => 'Public course']);
    $this->app['auth']->forgetGuards();
    $this->get(route('events.track', $public->slug))->assertOk()->assertStreamedContent('course-bytes');
    $this->get(route('events.show', $public->slug))->assertInertia(fn (Assert $page) => $page
        ->where('event.trackTitle', 'Public course')->missing('event.trackdraw_snapshot_path'));
    $empty = Event::factory()->published()->create();
    $this->get(route('events.track', $empty->slug))->assertNotFound();
    Storage::disk('s3')->delete('course.json');
    $this->get(route('events.track', $public->slug))->assertNotFound();
});

test('duplicate events keep their shared snapshot until the last attachment is removed', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $event = Event::factory()->create(['trackdraw_snapshot_path' => 'shared.json']);
    Storage::disk('s3')->put('shared.json', 'shared-course');
    $this->actingAs($admin)->post(route('admin.events.duplicate', $event))->assertRedirect();
    $copy = Event::query()->whereKeyNot($event->id)->sole();
    expect($copy->trackdraw_snapshot_path)->toBe('shared.json');
    $this->delete(route('admin.events.track.destroy', $event))->assertRedirect();
    Storage::disk('s3')->assertExists('shared.json');
    $this->delete(route('admin.events.destroy', $copy))->assertRedirect();
    Storage::disk('s3')->assertMissing('shared.json');
});

test('an event fetches with the selected account and remembers the connection without exposing its key', function () {
    $editor = User::factory()->create();
    $editor->assignRole(Role::Editor->value);
    TrackDrawConnection::factory()->create(['name' => 'DDS', 'api_key' => 'dds-secret']);
    $private = TrackDrawConnection::factory()->create(['name' => 'Private', 'api_key' => 'private-secret']);
    $event = Event::factory()->create();
    fakeTrackSnapshot();
    $this->actingAs($editor)->put(route('admin.events.track.update', $event), ['connection_id' => $private->id, 'project_id' => 'course-1'])
        ->assertSessionHasNoErrors();
    Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer private-secret'));
    expect($event->fresh()->track_draw_connection_id)->toBe($private->id);
    $this->get(route('admin.events.edit', $event))->assertInertia(fn (Assert $page) => $page
        ->where('event.track.connectionId', $private->id)->has('event.track.connections', 2)
        ->missing('event.track.connections.0.api_key')->missing('event.track.connections.1.api_key'));
});
