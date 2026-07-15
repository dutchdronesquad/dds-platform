<?php

use App\Enums\LocationEnvironment;
use App\Models\Event;
use App\Models\Location;
use App\Models\MediaAsset;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

test('locations can be created with structured and translatable venue data', function () {
    $location = Location::factory()
        ->withCoverImage()
        ->withDutchTranslation()
        ->create()
        ->load('coverImage');

    $this->assertModelExists($location);

    expect($location)
        ->name->toBeString()
        ->description->toHaveKeys(['en', 'nl'])
        ->facilities->toBeArray()
        ->environment->toBe(LocationEnvironment::Indoor)
        ->country_code->toBe('NL')
        ->floor_size_square_metres->toBeInt()
        ->ceiling_height_metres->toBeString()
        ->latitude->toBeString()
        ->longitude->toBeString()
        ->coverImage->toBeInstanceOf(MediaAsset::class);
});

test('location environments cover indoor and outdoor venues', function () {
    expect(array_column(LocationEnvironment::cases(), 'value'))->toBe([
        'indoor',
        'outdoor',
    ]);
});

test('location environments are enforced by the database', function () {
    $location = Location::factory()->create();

    expect(fn () => DB::table($location->getTable())
        ->where('id', $location->id)
        ->update(['environment' => 'unsupported']))
        ->toThrow(QueryException::class);
});

test('new locations mirror their database defaults before persistence', function () {
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
