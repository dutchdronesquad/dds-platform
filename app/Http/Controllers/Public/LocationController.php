<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Location;
use App\Support\MarkdownRenderer;
use App\Support\PublicEventData;
use App\Support\PublicLocationData;
use App\Support\SeoMetadata;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class LocationController extends Controller
{
    public function __construct(
        private PublicLocationData $locationData,
        private PublicEventData $eventData,
        private MarkdownRenderer $markdown,
    ) {}

    public function index(Request $request, SeoMetadata $seoMetadata): Response
    {
        $locations = Location::query()
            ->select([
                'id',
                'cover_image_id',
                'name',
                'slug',
                'description',
                'city',
                'environment',
            ])
            ->with(['coverImage:id,alt_text', 'coverImage.media'])
            ->orderBy('name')
            ->get()
            ->map(fn (Location $location): array => $this->locationData->summary($location));

        return Inertia::render('public/locations-index', [
            'locations' => $locations,
            'seo' => $seoMetadata->forPage('locations'),
        ]);
    }

    public function show(Location $location, SeoMetadata $seoMetadata): Response
    {
        $location->load(['coverImage:id,alt_text', 'coverImage.media']);

        $image = $this->locationData->image($location);
        $localizedDescription = $location->localizedDescription();
        $description = $this->markdown->toPlainText($localizedDescription)
            ?: "Bekijk de praktische informatie voor {$location->name}, een vlieglocatie van Dutch Drone Squad.";

        $upcomingEvents = $location->events()
            ->select([
                'id',
                'location_id',
                'season_id',
                'cover_image_id',
                'title',
                'slug',
                'content',
                'starts_at',
                'ends_at',
                'status',
                'type',
                'price_cents',
                'capacity',
                'registration_opens_at',
                'registration_deadline_at',
                'registration_status',
            ])
            ->publiclyVisible()
            ->upcoming()
            ->with([
                'location:id,name,city',
                'season:id,name,slug',
                'coverImage:id,alt_text',
                'coverImage.media',
            ])
            ->orderBy('starts_at')
            ->orderBy('id')
            ->limit(6)
            ->get()
            ->map(fn (Event $event): array => $this->eventData->summary($event));

        return Inertia::render('public/location-show', [
            'location' => [
                'id' => $location->id,
                'slug' => $location->slug,
                'name' => $location->name,
                'descriptionHtml' => $this->markdown->toHtml($localizedDescription),
                'city' => $location->city,
                'street' => $location->street,
                'houseNumber' => $location->house_number,
                'postalCode' => $location->postal_code,
                'countryCode' => $location->country_code,
                'environment' => $location->environment->value,
                'floorSizeSquareMetres' => $location->floor_size_square_metres,
                'ceilingHeightMetres' => $location->ceiling_height_metres,
                'facilities' => $location->facilities ?? [],
                'websiteUrl' => $location->website_url,
                'image' => $image,
                ...$this->locationData->googleMapsUrls($location),
            ],
            'upcomingEvents' => $upcomingEvents,
            'seo' => $seoMetadata->forPage('location', [
                'title' => $location->name,
                'description' => $description,
                'canonical_path' => route('locations.show', ['location' => $location->slug], false),
                'image_path' => $image['src'],
                'image_alt' => $image['alt'],
            ]),
        ]);
    }
}
