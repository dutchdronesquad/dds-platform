<?php

namespace App\Support;

use App\Models\Event;
use App\Models\MediaAsset;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class PublicEventData
{
    /**
     * @return array{
     *     id: int,
     *     slug: string,
     *     title: string,
     *     excerpt: string|null,
     *     startsAt: string,
     *     endsAt: string|null,
     *     status: string,
     *     type: string,
     *     priceCents: int|null,
     *     capacity: int|null,
     *     registrationStatus: string,
     *     season: array{name: string}|null,
     *     location: array{name: string, city: string},
     *     image: array{src: string, alt: string},
     * }
     */
    public function summary(Event $event): array
    {
        return [
            'id' => $event->id,
            'slug' => $event->slug,
            'title' => $event->title,
            'excerpt' => $event->content === null
                ? null
                : Str::limit(Str::squish($event->content), 150),
            'startsAt' => $event->starts_at->toIso8601String(),
            'endsAt' => $event->ends_at?->toIso8601String(),
            'status' => $event->status->value,
            'type' => $event->type->value,
            'priceCents' => $event->price_cents,
            'capacity' => $event->capacity,
            'registrationStatus' => $event->registration_status->value,
            'season' => $event->season === null
                ? null
                : ['name' => $event->season->name],
            'location' => [
                'name' => $event->location->name,
                'city' => $event->location->city,
            ],
            'image' => $this->image($event),
        ];
    }

    /** @return array{src: string, alt: string} */
    public function image(Event $event): array
    {
        if (! $event->coverImage instanceof MediaAsset) {
            return [
                'src' => '/images/dds/racing/indoor-track.jpg',
                'alt' => 'Indoor FPV-raceparcours van Dutch Drone Squad',
            ];
        }

        $altText = $event->coverImage->alt_text;

        return [
            'src' => Storage::disk($event->coverImage->disk)->url($event->coverImage->path),
            'alt' => Arr::get($altText, app()->getLocale())
                ?? Arr::get($altText, 'en')
                ?? $event->title,
        ];
    }
}
