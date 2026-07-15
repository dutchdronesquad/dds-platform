<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Support\PublicEventData;
use App\Support\SeoMetadata;
use Inertia\Inertia;
use Inertia\Response;

final class HomeController extends Controller
{
    public function __construct(private PublicEventData $eventData) {}

    public function __invoke(SeoMetadata $seoMetadata): Response
    {
        /** @var array<string, mixed> $homepage */
        $homepage = config('homepage');

        $upcomingEvents = Event::query()
            ->select([
                'id',
                'location_id',
                'cover_image_id',
                'title',
                'slug',
                'content',
                'starts_at',
                'ends_at',
                'status',
                'type',
                'registration_status',
            ])
            ->publiclyVisible()
            ->upcoming()
            ->with([
                'location:id,name,city',
                'coverImage:id,disk,path,alt_text',
            ])
            ->limit(3)
            ->get()
            ->map(fn (Event $event): array => $this->eventData->summary($event));

        return Inertia::render('welcome', [
            'latestNews' => $homepage['latestNews'],
            'latestNewsAreLegacy' => $homepage['latestNewsAreLegacy'],
            'partnerLogos' => $homepage['partnerLogos'],
            'seo' => $seoMetadata->forPage('home'),
            'upcomingEvents' => $upcomingEvents,
        ]);
    }
}
