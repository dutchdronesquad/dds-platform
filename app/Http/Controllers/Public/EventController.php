<?php

namespace App\Http\Controllers\Public;

use App\Enums\EventType;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Location;
use App\Support\PublicEventData;
use App\Support\SeoMetadata;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

final class EventController extends Controller
{
    public function __construct(private PublicEventData $eventData) {}

    public function index(Request $request, SeoMetadata $seoMetadata): Response
    {
        $activeType = EventType::tryFrom($request->string('type')->toString());
        $eventsQuery = Event::query()
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
                'registration_status',
            ])
            ->publiclyVisible()
            ->upcoming()
            ->with([
                'location:id,name,city',
                'season:id,name',
                'coverImage:id,disk,path,alt_text',
            ]);

        if ($activeType !== null) {
            $eventsQuery->where('type', $activeType);
        }

        $events = $eventsQuery
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Event $event): array => $this->eventData->summary($event));

        return Inertia::render('public/events-index', [
            'activeType' => $activeType?->value,
            'events' => $events,
            'seo' => $seoMetadata->forPage('events'),
            'typeFilters' => $this->typeFilters(),
        ]);
    }

    public function show(Event $event, SeoMetadata $seoMetadata): Response
    {
        abort_unless($event->isPubliclyVisible(), 404);

        $event->load([
            'location',
            'season:id,name',
            'coverImage:id,disk,path,alt_text',
        ]);

        $image = $this->eventData->image($event);
        $description = Str::limit(
            Str::squish($event->content ?? ''),
            155,
            '',
        ) ?: "Bekijk de praktische informatie voor {$event->title}, een event van Dutch Drone Squad.";

        return Inertia::render('public/event-show', [
            'event' => [
                ...$this->eventData->summary($event),
                'capacity' => $event->capacity,
                'content' => $event->content,
                'location' => [
                    'name' => $event->location->name,
                    'city' => $event->location->city,
                    'street' => $event->location->street,
                    'houseNumber' => $event->location->house_number,
                    'postalCode' => $event->location->postal_code,
                    ...$this->googleMapsUrls($event->location),
                ],
                'priceCents' => $event->price_cents,
                'registrationDeadlineAt' => $event->registration_deadline_at?->toIso8601String(),
                'registrationOpensAt' => $event->registration_opens_at?->toIso8601String(),
                'registrationUrl' => $event->registration_url,
            ],
            'seo' => $seoMetadata->forPage('event', [
                'title' => $event->title,
                'description' => $description,
                'canonical_path' => route('events.show', ['event' => $event->slug], false),
                'image_path' => $image['src'],
                'image_alt' => $image['alt'],
            ]),
        ]);
    }

    /** @return array{mapEmbedUrl: string, mapUrl: string} */
    private function googleMapsUrls(Location $location): array
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

    /** @return list<array{value: string, label: string}> */
    private function typeFilters(): array
    {
        return [
            ['value' => EventType::Training->value, 'label' => 'Trainingen'],
            ['value' => EventType::Race->value, 'label' => 'Races'],
            ['value' => EventType::Demo->value, 'label' => 'Demo’s'],
            ['value' => EventType::Workshop->value, 'label' => 'Workshops'],
            ['value' => EventType::Other->value, 'label' => 'Overig'],
        ];
    }
}
