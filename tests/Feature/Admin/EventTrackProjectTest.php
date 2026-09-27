<?php

use App\Enums\Role;
use App\Models\Event;
use App\Models\TrackDrawConnection;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    config(['services.trackdraw.url' => 'https://dev.trackdraw.app']);
    Http::preventStrayRequests();
});

test('editors can browse cloud project pages with the selected server side credentials', function () {
    $editor = User::factory()->create();
    $editor->assignRole(Role::Editor->value);
    $event = Event::factory()->create();
    TrackDrawConnection::factory()->create(['api_key' => 'unused-key']);
    $connection = TrackDrawConnection::factory()->create(['api_key' => 'selected-secret']);
    Http::fake(['https://dev.trackdraw.app/api/v1/projects*' => Http::sequence()
        ->push(['data' => [['id' => 'first', 'title' => 'Eerste baan', 'private' => 'hidden']], 'pagination' => ['has_more' => true, 'next_cursor' => 'next-page']])
        ->push(['data' => [['id' => 'second', 'title' => 'Tweede baan']], 'pagination' => ['has_more' => false, 'next_cursor' => null]])]);

    $this->actingAs($editor)->getJson(route('admin.events.track.projects', [$event, 'connection_id' => $connection->id]))
        ->assertOk()->assertExactJson(['projects' => [['id' => 'first', 'title' => 'Eerste baan']], 'nextCursor' => 'next-page'])
        ->assertHeader('Cache-Control', 'no-store, private');
    $this->getJson(route('admin.events.track.projects', [$event, 'connection_id' => $connection->id, 'cursor' => 'next-page']))
        ->assertOk()->assertExactJson(['projects' => [['id' => 'second', 'title' => 'Tweede baan']], 'nextCursor' => null]);

    Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer selected-secret') && $request['cursor'] === 'next-page' && $request['limit'] === 100);
    Http::assertSentCount(2);
    expect($event->fresh()->trackdraw_project_id)->toBeNull();
});

test('empty cloud accounts return an empty project list', function () {
    $editor = User::factory()->create();
    $editor->assignRole(Role::Editor->value);
    $event = Event::factory()->create();
    $connection = TrackDrawConnection::factory()->create();
    Http::fake(['https://dev.trackdraw.app/api/v1/projects*' => Http::response(['data' => [], 'pagination' => ['has_more' => false, 'next_cursor' => null]])]);

    $this->actingAs($editor)->getJson(route('admin.events.track.projects', [$event, 'connection_id' => $connection->id]))
        ->assertExactJson(['projects' => [], 'nextCursor' => null]);
    Http::assertSentCount(1);
});

test('cloud project errors return a safe 502 without changing the saved course', function (string $failure) {
    $editor = User::factory()->create();
    $editor->assignRole(Role::Editor->value);
    $event = Event::factory()->create(['trackdraw_project_id' => 'saved']);
    $connection = TrackDrawConnection::factory()->create(['api_key' => 'secret-key']);
    $payload = ['data' => [['id' => 'course', 'title' => 'Baan']], 'pagination' => ['has_more' => false, 'next_cursor' => null]];
    if ($failure === 'pagination') {
        $payload['pagination']['has_more'] = true;
    }
    if ($failure === 'project') {
        $payload['data'][0]['id'] = '../invalid';
    }
    Http::fake(['https://dev.trackdraw.app/api/v1/projects*' => match ($failure) {
        'connection' => Http::failedConnection(),
        'credentials' => Http::response(['message' => 'secret-key'], 401),
        'redirect' => Http::response('', 302, ['Location' => 'https://other.example']),
        'json' => Http::response('invalid'),
        default => Http::response($payload),
    }]);

    $this->actingAs($editor)->getJson(route('admin.events.track.projects', [$event, 'connection_id' => $connection->id]))
        ->assertStatus(502)->assertExactJson(['message' => 'De cloudprojecten konden niet worden opgehaald. Controleer de TrackDraw-koppeling en probeer opnieuw.']);
    expect($event->fresh()->trackdraw_project_id)->toBe('saved');
})->with(['credentials', 'connection', 'redirect', 'json', 'pagination', 'project']);

test('only event editors can browse projects and invalid connections never call TrackDraw', function () {
    $event = Event::factory()->create();
    $connection = TrackDrawConnection::factory()->create();
    $url = route('admin.events.track.projects', [$event, 'connection_id' => $connection->id]);
    $this->getJson($url)->assertUnauthorized();
    $this->actingAs(User::factory()->create())->getJson($url)->assertForbidden();
    $editor = User::factory()->create();
    $editor->assignRole(Role::Editor->value);
    $this->actingAs($editor)->getJson(route('admin.events.track.projects', [$event, 'connection_id' => 99999]))
        ->assertUnprocessable()->assertJsonValidationErrors('connection_id');
    $this->getJson(route('admin.events.track.projects', [$event, 'connection_id' => $connection->id, 'cursor' => str_repeat('a', 1025)]))
        ->assertUnprocessable()->assertJsonValidationErrors('cursor');
    Http::assertNothingSent();
});
