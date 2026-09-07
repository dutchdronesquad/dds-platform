<?php

use App\Enums\LocationEnvironment;
use App\Models\Event;
use App\Models\Location;
use App\Models\MediaAsset;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

test('locations expose structured venue casts and their cover image relationship', function () {
    $coverImage = MediaAsset::factory()->create();
    $location = Location::query()
        ->create([
            'cover_image_id' => $coverImage->id,
            'name' => 'Sportpaleis Alkmaar',
            'slug' => 'sportpaleis-alkmaar',
            'description' => [
                'en' => 'An indoor venue for FPV drone racing.',
                'nl' => 'Een binnenlocatie voor FPV-droneraces.',
            ],
            'street' => 'Terborchlaan',
            'house_number' => '200',
            'postal_code' => '1816 LE',
            'city' => 'Alkmaar',
            'country_code' => 'NL',
            'environment' => LocationEnvironment::Indoor->value,
            'floor_size_square_metres' => '1200',
            'ceiling_height_metres' => '8.50',
            'facilities' => ['parking', 'power'],
            'website_url' => 'https://example.com/venue',
            'latitude' => '52.6320000',
            'longitude' => '4.7450000',
        ])
        ->refresh()
        ->load('coverImage');

    $this->assertModelExists($location);

    expect($location)
        ->name->toBe('Sportpaleis Alkmaar')
        ->description->toHaveKeys(['en', 'nl'])
        ->facilities->toMatchArray(['parking' => 'free', 'power' => true])
        ->environment->toBe(LocationEnvironment::Indoor)
        ->country_code->toBe('NL')
        ->floor_size_square_metres->toBe(1200)
        ->ceiling_height_metres->toBe('8.50')
        ->latitude->toBe('52.6320000')
        ->longitude->toBe('4.7450000')
        ->and($location->coverImage?->id)->toBe($coverImage->id);
});

test('location environments are enforced by the database', function () {
    $location = Location::factory()->create();

    expect(fn () => DB::table($location->getTable())
        ->where('id', $location->id)
        ->update(['environment' => 'unsupported']))
        ->toThrow(QueryException::class);
});

test('new locations default to the Netherlands', function () {
    $location = new Location;

    expect($location->country_code)->toBe('NL');
});

test('deleting a cover image keeps the location and clears the reference', function () {
    $location = Location::factory()->withCoverImage()->create()->load('coverImage');
    $coverImage = $location->coverImage;

    $coverImage->delete();

    expect($location->refresh()->cover_image_id)->toBeNull();
});

test('locations referenced by events cannot be deleted', function () {
    $event = Event::factory()->create();

    expect(fn () => $event->location->delete())
        ->toThrow(QueryException::class);
});

test('localized description resolves the current locale, then english, then any non-empty translation', function () {
    $bothLocales = Location::factory()->make([
        'description' => ['en' => 'English copy.', 'nl' => 'Nederlandse tekst.'],
    ]);

    app()->setLocale('nl');
    expect($bothLocales->localizedDescription())->toBe('Nederlandse tekst.');

    app()->setLocale('en');
    expect($bothLocales->localizedDescription())->toBe('English copy.');

    $dutchOnly = Location::factory()->make([
        'description' => ['nl' => 'Alleen Nederlands.'],
    ]);
    expect($dutchOnly->localizedDescription())->toBe('Alleen Nederlands.');

    $empty = Location::factory()->make(['description' => []]);
    expect($empty->localizedDescription())->toBeNull();
});

test('legacy facilities migrate with free parking and preserve other details', function () {
    $location = Location::factory()->create();
    DB::table('locations')->where('id', $location->id)->update(['facilities' => json_encode(['parking', 'wifi', 'catering', 'power', 'Eigen pitruimte'])]);
    $migration = require database_path('migrations/2026_09_07_212317_structure_location_facilities.php');

    $migration->up();

    expect($location->refresh()->facilities)->toMatchArray(['parking' => 'free', 'wifi' => 'available', 'catering' => 'available', 'power' => true, 'legacy' => ['Eigen pitruimte']]);
    $migration->up();
    $migration->down();
    expect($location->refresh()->facilities)->toMatchArray(['parking' => 'free', 'legacy' => ['Eigen pitruimte']]);
});

test('power and charging migrate to a single facility without losing availability', function (bool $power, bool $charging, bool $expected) {
    $location = Location::factory()->create();
    DB::table('locations')->where('id', $location->id)->update(['facilities' => json_encode(['power' => $power, 'charging' => $charging, 'legacy' => ['Eigen pitruimte']])]);
    $migration = require database_path('migrations/2026_09_07_220912_simplify_location_facilities.php');

    $migration->up();

    expect($location->refresh()->facilities)
        ->toMatchArray(['power' => $expected, 'legacy' => ['Eigen pitruimte']])
        ->not->toHaveKey('charging');
})->with([
    'neither' => [false, false, false],
    'power only' => [true, false, true],
    'charging only' => [false, true, true],
    'both' => [true, true, true],
]);

test('facility simplification migrates legacy choices and preserves explicit choices', function (array $before, array $after) {
    $location = Location::factory()->create();
    DB::table('locations')->where('id', $location->id)->update(['facilities' => json_encode($before)]);
    $migration = require database_path('migrations/2026_09_07_220912_simplify_location_facilities.php');

    $migration->up();
    $migration->up();

    expect($location->refresh()->facilities)->toMatchArray($after)->not->toHaveKey('spectator_seating');
})->with([
    'unknown types' => [['wifi' => 'available', 'catering' => 'available'], ['wifi' => 'private', 'catering' => 'nearby']],
    'staff wifi' => [['wifi' => 'staff_only'], ['wifi' => 'private']],
    'public wifi and catering on site' => [['wifi' => 'public', 'catering' => 'on_site'], ['wifi' => 'public', 'catering' => 'on_site']],
    'absent facilities' => [['wifi' => 'none', 'catering' => 'none', 'spectator_area' => false, 'spectator_seating' => false], ['wifi' => 'none', 'catering' => 'none', 'spectator_area' => false]],
    'seating only' => [['spectator_seating' => true, 'spectator_area' => false], ['spectator_area' => true]],
    'area only' => [['spectator_seating' => false, 'spectator_area' => true], ['spectator_area' => true]],
    'seating and area' => [['spectator_seating' => true, 'spectator_area' => true], ['spectator_area' => true]],
]);
