<?php

namespace App\Support;

use App\Models\Location;
use App\Models\MediaAsset;
use Illuminate\Support\Arr;

final class PublicLocationData
{
    /**
     * @return array{
     *     id: int,
     *     slug: string,
     *     name: string,
     *     excerpt: string|null,
     *     city: string,
     *     environment: string,
     *     image: array{src: string, alt: string},
     * }
     */
    public function summary(Location $location): array
    {
        return [
            'id' => $location->id,
            'slug' => $location->slug,
            'name' => $location->name,
            'excerpt' => $location->localizedDescription(),
            'city' => $location->city,
            'environment' => $location->environment->value,
            'image' => $this->image($location),
        ];
    }

    /** @return array{src: string, alt: string} */
    public function image(Location $location): array
    {
        if (! $location->coverImage instanceof MediaAsset) {
            return [
                'src' => '/images/dds/racing/indoor-track.jpg',
                'alt' => "Indoor vlieglocatie van Dutch Drone Squad: {$location->name}",
            ];
        }

        $altText = $location->coverImage->alt_text;

        return [
            'src' => $location->coverImage->url(),
            'alt' => Arr::get($altText, app()->getLocale())
                ?? Arr::get($altText, 'en')
                ?? $location->name,
        ];
    }

    /** @return array{mapEmbedUrl: string, mapUrl: string} */
    public function googleMapsUrls(Location $location): array
    {
        $query = implode(', ', [
            $location->name,
            "{$location->street} {$location->house_number}",
            "{$location->postal_code} {$location->city}",
            $location->country_code,
        ]);

        return [
            'mapEmbedUrl' => 'https://maps.google.com/maps?'.http_build_query([
                'q' => $query,
                'z' => 15,
                'output' => 'embed',
            ], encoding_type: PHP_QUERY_RFC3986),
            'mapUrl' => 'https://www.google.com/maps/search/?'.http_build_query([
                'api' => 1,
                'query' => $query,
            ], encoding_type: PHP_QUERY_RFC3986),
        ];
    }
}
