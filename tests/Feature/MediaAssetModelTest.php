<?php

use App\Models\Event;
use App\Models\MediaAsset;
use App\Support\PublicEventData;

test('media assets expose typed image metadata and translated alt text', function () {
    $mediaAsset = MediaAsset::query()->create([
        'disk' => 'public',
        'path' => 'events/winter-race.jpg',
        'original_filename' => 'winter-race.jpg',
        'mime_type' => 'image/jpeg',
        'size_bytes' => '2048',
        'width' => '1920',
        'height' => '1080',
        'alt_text' => [
            'en' => 'Pilots racing FPV drones indoors.',
            'nl' => 'Piloten racen binnen met FPV-drones.',
        ],
    ]);

    $this->assertModelExists($mediaAsset);

    expect($mediaAsset)
        ->disk->toBe('public')
        ->path->toBe('events/winter-race.jpg')
        ->original_filename->toBe('winter-race.jpg')
        ->mime_type->toBe('image/jpeg')
        ->size_bytes->toBe(2048)
        ->width->toBe(1920)
        ->height->toBe(1080)
        ->alt_text->toBe([
            'en' => 'Pilots racing FPV drones indoors.',
            'nl' => 'Piloten racen binnen met FPV-drones.',
        ]);
});

test('event images use localized alt text and fall back to the event title', function () {
    $eventWithoutAltText = Event::factory()->create([
        'title' => 'Indoor training',
        'cover_image_id' => MediaAsset::factory()->create([
            'alt_text' => null,
        ]),
    ])->load('coverImage');
    $localizedEvent = Event::factory()->create([
        'title' => 'Community race',
        'cover_image_id' => MediaAsset::factory()->create([
            'alt_text' => [
                'nl' => 'Piloten racen binnen met FPV-drones.',
            ],
        ]),
    ])->load('coverImage');

    $eventData = app(PublicEventData::class);

    expect($eventData->image($eventWithoutAltText))
        ->alt->toBe('Indoor training');

    app()->setLocale('nl');

    expect($eventData->image($localizedEvent))
        ->alt->toBe('Piloten racen binnen met FPV-drones.');
});

test('new media assets default to the public disk', function () {
    $mediaAsset = new MediaAsset;

    expect($mediaAsset->disk)->toBe('public');
});
