<?php

use App\Models\MediaAsset;

test('media assets can be created through their factory', function () {
    $mediaAsset = MediaAsset::factory()->create([
        'alt_text' => [
            'en' => 'Pilots racing FPV drones indoors.',
            'nl' => 'Piloten racen binnen met FPV-drones.',
        ],
    ]);

    $this->assertModelExists($mediaAsset);

    expect($mediaAsset)
        ->disk->toBe('public')
        ->path->not->toBeEmpty()
        ->original_filename->toEndWith('.jpg')
        ->mime_type->toBe('image/jpeg')
        ->size_bytes->toBeInt()
        ->width->toBeInt()
        ->height->toBeInt()
        ->alt_text->toHaveKeys(['en', 'nl'])
        ->is_decorative->toBeFalse();
});

test('pdf media omits image dimensions', function () {
    $mediaAsset = MediaAsset::factory()->pdf()->create();

    expect($mediaAsset)
        ->original_filename->toEndWith('.pdf')
        ->mime_type->toBe('application/pdf')
        ->width->toBeNull()
        ->height->toBeNull();
});

test('decorative media explicitly omits alt text', function () {
    $mediaAsset = MediaAsset::factory()->decorative()->create();

    expect($mediaAsset->is_decorative)->toBeTrue()
        ->and($mediaAsset->alt_text)->toBeNull();
});

test('new media assets mirror their database defaults before persistence', function () {
    $mediaAsset = new MediaAsset;

    expect($mediaAsset->disk)->toBe('public')
        ->and($mediaAsset->is_decorative)->toBeFalse();
});
