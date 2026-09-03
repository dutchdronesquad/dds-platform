<?php

use App\Enums\LocationEnvironment;
use App\Enums\Role;
use App\Models\Event;
use App\Models\Location;
use App\Models\MediaAsset;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('location management requires a management role and location permission', function () {
    $this->get(route('admin.locations.index'))->assertRedirect(route('login'));

    $user = User::factory()->create();
    $location = Location::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.locations.index'))
        ->assertForbidden();

    expect($user->can('view', $location))->toBeFalse();
});

test('admins can review locations with events counts and capabilities', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $location = Location::factory()->create([
        'name' => 'Sportpaleis Alkmaar',
        'city' => 'Alkmaar',
    ]);
    Event::factory()->for($location)->create();

    $this->actingAs($admin)
        ->get(route('admin.locations.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/locations/index')
            ->where('canCreate', true)
            ->has('locations.data', 1)
            ->where('locations.data.0.id', $location->id)
            ->where('locations.data.0.name', 'Sportpaleis Alkmaar')
            ->where('locations.data.0.eventsCount', 1)
            ->where('locations.data.0.capabilities.update', true)
            ->where('locations.data.0.capabilities.delete', false),
        );
});

test('location search filters by name or city', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    Location::factory()->create(['name' => 'Sportpaleis Alkmaar', 'city' => 'Alkmaar']);
    Location::factory()->create(['name' => 'Sportcentrum Koggenhal', 'city' => 'De Goorn']);

    $this->actingAs($admin)
        ->get(route('admin.locations.index', ['search' => 'ALKMAAR']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.search', 'ALKMAAR')
            ->has('locations.data', 1)
            ->where('locations.data.0.name', 'Sportpaleis Alkmaar'),
        );
});

test('admins can open location forms with complete options and editable values', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $coverImage = MediaAsset::factory()->create();
    $location = Location::factory()->withCoverImage()->create([
        'cover_image_id' => $coverImage->id,
        'name' => 'Sportpaleis Alkmaar',
        'slug' => 'sportpaleis-alkmaar',
        'description' => ['en' => 'An indoor venue.', 'nl' => 'Een binnenlocatie.'],
        'environment' => LocationEnvironment::Indoor,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.locations.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/locations/create')
            ->has('options.environments', 2),
        );

    $this->get(route('admin.locations.edit', $location))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/locations/edit')
            ->where('location.id', $location->id)
            ->where('location.name', 'Sportpaleis Alkmaar')
            ->where('location.slug', 'sportpaleis-alkmaar')
            ->where('location.description.en', 'An indoor venue.')
            ->where('location.description.nl', 'Een binnenlocatie.')
            ->where('location.environment', LocationEnvironment::Indoor->value)
            ->where('location.eventsCount', 0)
            ->where('location.capabilities.delete', true)
            ->has('location.coverImage'),
        );
});

test('editors can create and update locations but cannot delete them', function () {
    $editor = User::factory()->create();
    $editor->assignRole(Role::Editor->value);

    $this->actingAs($editor)
        ->post(route('admin.locations.store'), validLocationPayload([
            'name' => 'Editor Hal',
            'slug' => '',
        ]))
        ->assertRedirect();

    $location = Location::query()->where('slug', 'editor-hal')->firstOrFail();

    $this->actingAs($editor)
        ->put(route('admin.locations.update', $location), validLocationPayload([
            'name' => 'Bijgewerkte Editor Hal',
            'slug' => 'bijgewerkte-editor-hal',
        ]))
        ->assertRedirect(route('admin.locations.edit', $location));

    expect($location->refresh()->name)->toBe('Bijgewerkte Editor Hal');

    $this->actingAs($editor)
        ->delete(route('admin.locations.destroy', $location))
        ->assertForbidden();

    $this->assertModelExists($location);
});

test('admins can create locations with normalized coordinates and generated slugs', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    $this->actingAs($admin)
        ->post(route('admin.locations.store'), validLocationPayload([
            'name' => 'Sportpaleis Alkmaar',
            'slug' => '',
        ]))
        ->assertRedirect();

    $location = Location::query()->where('slug', 'sportpaleis-alkmaar')->firstOrFail();

    expect($location)
        ->name->toBe('Sportpaleis Alkmaar')
        ->description->toBe(['nl' => 'Een binnenlocatie voor FPV-droneraces.'])
        ->environment->toBe(LocationEnvironment::Indoor)
        ->latitude->toBe('52.6317600')
        ->longitude->toBe('4.7336300');
});

test('location requests reject invalid coordinates and website urls', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    $this->actingAs($admin)
        ->post(route('admin.locations.store'), validLocationPayload([
            'latitude' => '95',
            'longitude' => '-190',
            'website_url' => 'javascript:alert(1)',
        ]))
        ->assertSessionHasErrors([
            'latitude',
            'longitude',
            'website_url',
        ]);
});

test('location requests require a Dutch description', function () {
    $admin = User::factory()->create(['locale' => 'en']);
    $admin->assignRole(Role::Admin->value);
    $expectedMessage = trans('validation.required', [
        'attribute' => 'omschrijving',
    ], 'en');

    $this->actingAs($admin)
        ->post(route('admin.locations.store'), validLocationPayload([
            'description' => ['en' => 'Only English copy.'],
        ]))
        ->assertSessionHasErrors([
            'description.nl' => $expectedMessage,
        ]);
});

test('admins can delete unused locations', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $location = Location::factory()->create();

    $this->actingAs($admin)
        ->delete(route('admin.locations.destroy', $location))
        ->assertRedirect(route('admin.locations.index'))
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Locatie verwijderd.',
        ]);

    $this->assertModelMissing($location);
});

test('deleting a location with events fails cleanly with a flash message instead of a server error', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $location = Location::factory()->create();
    Event::factory()->for($location)->create();

    $this->actingAs($admin)
        ->delete(route('admin.locations.destroy', $location))
        ->assertRedirect(route('admin.locations.edit', $location))
        ->assertInertiaFlash('toast', [
            'type' => 'error',
            'message' => 'Deze locatie kan niet worden verwijderd omdat er nog events aan gekoppeld zijn.',
        ]);

    $this->assertModelExists($location);
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function validLocationPayload(array $overrides = []): array
{
    return [
        'name' => 'Sportpaleis Alkmaar',
        'slug' => 'sportpaleis-alkmaar',
        'description' => [
            'nl' => 'Een binnenlocatie voor FPV-droneraces.',
        ],
        'street' => 'Terborchlaan',
        'house_number' => '200',
        'postal_code' => '1816 LE',
        'city' => 'Alkmaar',
        'country_code' => 'NL',
        'environment' => LocationEnvironment::Indoor->value,
        'floor_size_square_metres' => 1200,
        'ceiling_height_metres' => '8.50',
        'facilities' => ['parking', 'power'],
        'website_url' => 'https://example.com/venue',
        'latitude' => '52.6317600',
        'longitude' => '4.7336300',
        ...$overrides,
    ];
}
