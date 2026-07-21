<?php

use App\Models\Event;
use App\Models\MediaAsset;
use App\Support\PublicEventData;

test('media assets expose package-managed file metadata and translated alt text', function () {
    $mediaAsset = MediaAsset::factory()
        ->named('winter-race.jpg')
        ->create([
            'alt_text' => [
                'en' => 'Pilots racing FPV drones indoors.',
                'nl' => 'Piloten racen binnen met FPV-drones.',
            ],
        ]);

    $mediaAsset->file()?->update([
        'size' => 2048,
        'custom_properties' => [
            'width' => 1920,
            'height' => 1080,
        ],
    ]);

    $this->assertModelExists($mediaAsset);

    expect($mediaAsset)
        ->filename()->toBe('winter-race.jpg')
        ->mimeType()->toBe('image/jpeg')
        ->sizeBytes()->toBe(2048)
        ->width()->toBe(1920)
        ->height()->toBe(1080)
        ->alt_text->toBe([
            'en' => 'Pilots racing FPV drones indoors.',
            'nl' => 'Piloten racen binnen met FPV-drones.',
        ])
        ->and($mediaAsset->file()?->model_id)->toBe($mediaAsset->id);
});

test('event images use localized alt text and fall back to the event title', function () {
    $eventWithoutAltText = Event::factory()->create([
        'title' => 'Indoor training',
        'cover_image_id' => MediaAsset::factory()->create([
            'alt_text' => null,
        ]),
    ])->load('coverImage.media');
    $localizedEvent = Event::factory()->create([
        'title' => 'Community race',
        'cover_image_id' => MediaAsset::factory()->create([
            'alt_text' => [
                'nl' => 'Piloten racen binnen met FPV-drones.',
            ],
        ]),
    ])->load('coverImage.media');

    $eventData = app(PublicEventData::class);

    expect($eventData->image($eventWithoutAltText))
        ->alt->toBe('Indoor training');

    app()->setLocale('nl');

    expect($eventData->image($localizedEvent))
        ->alt->toBe('Piloten racen binnen met FPV-drones.');
});

test('factory media uses the configured media disk', function () {
    $mediaAsset = MediaAsset::factory()->create();

    expect($mediaAsset->disk())->toBe(config('media-library.disk_name'));
});
