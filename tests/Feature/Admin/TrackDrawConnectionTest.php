<?php

use App\Enums\Role;
use App\Models\Event;
use App\Models\TrackDrawConnection;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('s3');
});

test('only administrators can manage integrations and their saved credentials', function () {
    $connection = TrackDrawConnection::factory()->create();
    $this->get(route('admin.integrations.index'))->assertRedirect(route('login'));
    $editor = User::factory()->create();
    $editor->assignRole(Role::Editor->value);
    $this->actingAs($editor)->get(route('admin.integrations.index'))->assertForbidden();
    $this->get(route('admin.integrations.trackdraw.index'))->assertForbidden();
    $this->post(route('admin.integrations.trackdraw.store'), ['name' => 'Private', 'api_key' => 'secret-key'])->assertForbidden();
    $this->put(route('admin.integrations.trackdraw.update', $connection), ['name' => 'Private', 'api_key' => 'secret-key'])->assertForbidden();
    $this->delete(route('admin.integrations.trackdraw.destroy', $connection))->assertForbidden();
    expect($connection->fresh()->api_key)->toBe('test-trackdraw-key');
});

test('an administrator manages multiple named encrypted keys without exposing credentials', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $this->actingAs($admin)->post(route('admin.integrations.trackdraw.store'), ['name' => 'DDS', 'api_key' => 'first-secret'])->assertSessionHasNoErrors();
    $first = TrackDrawConnection::query()->sole();
    Event::factory()->create(['track_draw_connection_id' => $first->id]);
    $this->post(route('admin.integrations.trackdraw.store'), ['name' => 'Private', 'api_key' => 'second-secret'])->assertSessionHasNoErrors();
    expect(TrackDrawConnection::query()->count())->toBe(2)
        ->and($first->api_key)->toBe('first-secret')
        ->and($first->getRawOriginal('api_key'))->not->toContain('first-secret')
        ->and($first->toArray())->not->toHaveKey('api_key');
    $this->get(route('admin.integrations.index'))->assertInertia(fn (Assert $page) => $page->where('trackDrawConnectionCount', 2));
    $this->get(route('admin.integrations.trackdraw.index'))->assertInertia(fn (Assert $page) => $page
        ->has('connections.data', 2)->where('connections.data.0.name', 'DDS')->where('connections.data.0.events_count', 1)->where('connections.data.1.events_count', 0)->missing('connections.data.0.api_key')->missing('connections.data.1.api_key'));
    $this->put(route('admin.integrations.trackdraw.update', $first), ['name' => 'DDS racing', 'api_key' => 'replacement-secret'])->assertSessionHasNoErrors();
    $this->put(route('admin.integrations.trackdraw.update', $first), ['name' => 'DDS renamed', 'api_key' => ''])->assertSessionHasNoErrors();
    expect($first->fresh()->api_key)->toBe('replacement-secret')
        ->and($first->fresh()->name)->toBe('DDS renamed')
        ->and(TrackDrawConnection::query()->where('name', 'Private')->sole()->api_key)->toBe('second-secret');
    $this->put(route('admin.integrations.trackdraw.update', $first), ['name' => 'DDS', 'api_key' => str_repeat('s', 1001)])
        ->assertSessionHasErrors('api_key')->assertSessionMissing('_old_input.api_key');
    $this->post(route('admin.integrations.trackdraw.store'), ['name' => '', 'api_key' => 'secret'])
        ->assertSessionHasErrors('name')->assertSessionMissing('_old_input.api_key');
    expect($first->fresh()->api_key)->toBe('replacement-secret')
        ->and(config('nightwatch.redact_payload_fields'))->toContain('api_key');
});

test('removing one connection preserves its saved courses and other accounts', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $connection = TrackDrawConnection::factory()->create();
    $other = TrackDrawConnection::factory()->create();
    $event = Event::factory()->published()->create([
        'track_draw_connection_id' => $connection->id,
        'trackdraw_snapshot_path' => 'saved.json',
        'trackdraw_title' => 'Saved',
    ]);
    Storage::disk('s3')->put('saved.json', 'saved-course');
    $this->actingAs($admin)->delete(route('admin.integrations.trackdraw.destroy', $connection))->assertRedirect();
    expect(TrackDrawConnection::query()->sole()->id)->toBe($other->id)
        ->and($event->fresh()->track_draw_connection_id)->toBeNull();
    $this->get(route('events.track', $event->slug))->assertOk()->assertStreamedContent('saved-course');
});
