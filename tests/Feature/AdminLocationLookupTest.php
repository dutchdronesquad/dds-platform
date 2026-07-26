<?php

use App\Enums\Role;
use App\Models\MediaAsset;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('address suggestions require a location permission', function () {
    $this->get(route('admin.locations.address-suggestions', ['q' => 'Terborchlaan']))
        ->assertRedirect(route('login'));

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.locations.address-suggestions', ['q' => 'Terborchlaan']))
        ->assertForbidden();
});

test('address suggestions list candidate PDOK addresses for a query', function () {
    Http::fake([
        'api.pdok.nl/*suggest*' => Http::response([
            'response' => [
                'docs' => [
                    [
                        'id' => 'adr-345d817fa01b8f2751c8dd63761ec4b4',
                        'weergavenaam' => 'Terborchlaan 200, 1816LE Alkmaar',
                    ],
                ],
            ],
        ]),
    ]);

    $editor = User::factory()->create();
    $editor->assignRole(Role::Editor->value);

    $this->actingAs($editor)
        ->get(route('admin.locations.address-suggestions', ['q' => 'Terborchlaan 200 Alkmaar']))
        ->assertOk()
        ->assertJson([
            'data' => [
                [
                    'id' => 'adr-345d817fa01b8f2751c8dd63761ec4b4',
                    'label' => 'Terborchlaan 200, 1816LE Alkmaar',
                    'source' => 'pdok',
                    'resolved' => null,
                ],
            ],
        ]);

    Http::assertNotSent(fn ($request): bool => str_contains($request->url(), 'photon.komoot.io'));
});

test('address suggestions fall back to Photon when PDOK has no match', function () {
    Http::fake([
        'api.pdok.nl/*suggest*' => Http::response(['response' => ['docs' => []]]),
        'photon.komoot.io/*' => Http::response([
            'features' => [
                [
                    'properties' => [
                        'street' => 'Downing Street',
                        'housenumber' => '10',
                        'postcode' => 'SW1A 2AA',
                        'city' => 'London',
                        'countrycode' => 'gb',
                    ],
                    'geometry' => [
                        'coordinates' => [-0.1276965, 51.5034878],
                    ],
                ],
            ],
        ]),
    ]);

    $editor = User::factory()->create();
    $editor->assignRole(Role::Editor->value);

    $this->actingAs($editor)
        ->get(route('admin.locations.address-suggestions', ['q' => '10 Downing Street London']))
        ->assertOk()
        ->assertJson([
            'data' => [
                [
                    'id' => 'photon-0',
                    'label' => 'Downing Street 10, SW1A 2AA London, GB',
                    'source' => 'photon',
                    'resolved' => [
                        'street' => 'Downing Street',
                        'houseNumber' => '10',
                        'postalCode' => 'SW1A 2AA',
                        'city' => 'London',
                        'countryCode' => 'GB',
                        'latitude' => '51.5034878',
                        'longitude' => '-0.1276965',
                    ],
                ],
            ],
        ]);
});

test('address suggestions require at least three characters', function () {
    $editor = User::factory()->create();
    $editor->assignRole(Role::Editor->value);

    $this->actingAs($editor)
        ->get(route('admin.locations.address-suggestions', ['q' => 'ab']))
        ->assertInvalid(['q']);
});

test('address lookup requires a location permission', function () {
    $this->get(route('admin.locations.lookup-address', ['id' => 'adr-345d817fa01b8f2751c8dd63761ec4b4', 'source' => 'pdok']))
        ->assertRedirect(route('login'));

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.locations.lookup-address', ['id' => 'adr-345d817fa01b8f2751c8dd63761ec4b4', 'source' => 'pdok']))
        ->assertForbidden();
});

test('address lookup resolves the full structured address and coordinates for a PDOK suggestion', function () {
    Http::fake([
        'api.pdok.nl/*lookup*' => Http::response([
            'response' => [
                'docs' => [
                    [
                        'straatnaam' => 'Terborchlaan',
                        'huisnummer' => 200,
                        'postcode' => '1816LE',
                        'woonplaatsnaam' => 'Alkmaar',
                        'centroide_ll' => 'POINT(4.71593677 52.63472801)',
                    ],
                ],
            ],
        ]),
    ]);

    $editor = User::factory()->create();
    $editor->assignRole(Role::Editor->value);

    $this->actingAs($editor)
        ->get(route('admin.locations.lookup-address', ['id' => 'adr-345d817fa01b8f2751c8dd63761ec4b4', 'source' => 'pdok']))
        ->assertOk()
        ->assertJson([
            'data' => [
                'street' => 'Terborchlaan',
                'houseNumber' => '200',
                'postalCode' => '1816LE',
                'city' => 'Alkmaar',
                'countryCode' => 'NL',
                'latitude' => '52.6347280',
                'longitude' => '4.7159368',
            ],
        ]);
});

test('address lookup returns a friendly error when nothing is found', function () {
    Http::fake([
        'api.pdok.nl/*lookup*' => Http::response(['response' => ['docs' => []]]),
    ]);

    $editor = User::factory()->create();
    $editor->assignRole(Role::Editor->value);

    $this->actingAs($editor)
        ->get(route('admin.locations.lookup-address', ['id' => 'adr-unknown', 'source' => 'pdok']))
        ->assertStatus(422)
        ->assertJsonStructure(['message']);
});

test('address lookup rejects an unsupported source', function () {
    $editor = User::factory()->create();
    $editor->assignRole(Role::Editor->value);

    $this->actingAs($editor)
        ->get(route('admin.locations.lookup-address', ['id' => 'photon-0', 'source' => 'photon']))
        ->assertStatus(422)
        ->assertJsonStructure(['message']);
});

test('media quick upload requires the create media permission', function () {
    Storage::fake('public');

    $this->post(route('admin.media.quick-upload'), [
        'file' => UploadedFile::fake()->image('cover.jpg', 800, 600),
    ])->assertRedirect(route('login'));

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('admin.media.quick-upload'), [
            'file' => UploadedFile::fake()->image('cover.jpg', 800, 600),
        ])
        ->assertForbidden();
});

test('media quick upload stores the file and returns picker-ready data', function () {
    Storage::fake('public');

    $editor = User::factory()->create();
    $editor->assignRole(Role::Editor->value);

    $response = $this->actingAs($editor)
        ->post(route('admin.media.quick-upload'), [
            'file' => UploadedFile::fake()->image('cover.jpg', 800, 600),
        ])
        ->assertCreated()
        ->assertJsonStructure(['data' => ['id', 'filename', 'url', 'isImage', 'width', 'height']]);

    $mediaAssetId = $response->json('data.id');

    expect(MediaAsset::query()->whereKey($mediaAssetId)->exists())->toBeTrue();
});
