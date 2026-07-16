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
        ->facilities->toBe(['parking', 'power'])
        ->environment->toBe(LocationEnvironment::Indoor)
        ->country_code->toBe('NL')
        ->floor_size_square_metres->toBe(1200)
        ->ceiling_height_metres->toBe('8.50')
        ->latitude->toBe('52.6320000')
        ->longitude->toBe('4.7450000')
        ->coverImage->id->toBe($coverImage->id);
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
