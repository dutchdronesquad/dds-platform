<?php

namespace App\Http\Controllers\Public;

use App\Enums\EventRegistrationStatus;
use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Support\MarkdownRenderer;
use App\Support\PublicEventData;
use App\Support\PublicLocationData;
use App\Support\PublicSeasonData;
use App\Support\SeoMetadata;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class EventController extends Controller
{
    public function __construct(
        private PublicEventData $eventData,
        private PublicSeasonData $seasonData,
        private PublicLocationData $locationData,
        private MarkdownRenderer $markdown,
    ) {}

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
                'registration_enabled',
                'registration_closed_manually',
                'registration_full',
                'registration_waitlist_enabled',
                'registration_opens_at',
                'registration_deadline_at',
            ])
            ->publiclyVisible()
            ->upcoming()
            ->with([
                'location:id,name,city',
                'season:id,name,slug',
                'coverImage:id,alt_text',
                'coverImage.media',
            ]);

        if ($activeType !== null) {
            $eventsQuery->where('type', $activeType);
        }

        $currentSeason = null;

        if (
            $activeType === null
            || in_array($activeType, [EventType::Training, EventType::Race], true)
        ) {
            $firstSeasonEvent = (clone $eventsQuery)
                ->whereNotNull('season_id')
                ->first();

            if ($firstSeasonEvent?->season !== null) {
                $currentSeason = $this->seasonData->summary(
                    $firstSeasonEvent->season,
                    $activeType,
                );
            }
        }

        $events = $eventsQuery
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Event $event): array => $this->eventData->summary($event));

        return Inertia::render('public/events-index', [
            'activeType' => $activeType?->value,
            'currentSeason' => $currentSeason,
            'events' => $events,
            'seo' => $seoMetadata->forPage('events'),
            'typeFilters' => $this->typeFilters(),
        ]);
    }

    public function show(Event $event, SeoMetadata $seoMetadata): Response
    {
        abort_unless($event->isPubliclyVisible(), 404);

        return $this->renderEvent($event, $seoMetadata);
    }

    public function preview(Event $event, SeoMetadata $seoMetadata): Response
    {
        Gate::authorize('view', $event);

        return $this->renderEvent($event, $seoMetadata, true);
    }

    private function renderEvent(
        Event $event,
        SeoMetadata $seoMetadata,
        bool $isPreview = false,
    ): Response {
        $event->load([
            'location',
            'season:id,name,slug',
            'coverImage:id,alt_text',
            'coverImage.media',
        ]);

        $image = $this->eventData->image($event);
        $seasonContext = $event->season === null
            ? null
            : $this->seasonData->summary($event->season);
        $description = str($this->markdown->toPlainText($event->content))->limit(
            155,
            '',
        )->toString() ?: "Bekijk de praktische informatie voor {$event->title}, een event van Dutch Drone Squad.";

        return Inertia::render('public/event-show', [
            'event' => [
                ...$this->eventData->summary($event),
                'contentHtml' => $this->markdown->toHtml($event->content),
                'location' => [
                    'name' => $event->location->name,
                    'city' => $event->location->city,
                    'street' => $event->location->street,
                    'houseNumber' => $event->location->house_number,
                    'postalCode' => $event->location->postal_code,
                    ...$this->locationData->googleMapsUrls($event->location),
                ],
                'registrationUrl' => $event->status !== EventStatus::Cancelled
                    && in_array($event->currentRegistrationStatus(), [
                        EventRegistrationStatus::Open,
                        EventRegistrationStatus::Waitlist,
                    ], true) ? $event->registration_url : null,
                'seasonContext' => $seasonContext,
            ],
            'seo' => $seoMetadata->forPage('event', [
                'title' => $event->title,
                'description' => $description,
                'canonical_path' => route('events.show', ['event' => $event->slug], false),
                'image_path' => $image['src'],
                'image_alt' => $image['alt'],
                ...($isPreview ? ['robots' => 'noindex, nofollow'] : []),
            ]),
            'isPreview' => $isPreview,
        ]);
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
