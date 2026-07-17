<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Season;
use App\Support\PublicEventData;
use App\Support\PublicSeasonData;
use App\Support\SeoMetadata;
use Inertia\Inertia;
use Inertia\Response;

final class SeasonController extends Controller
{
    public function __construct(
        private PublicEventData $eventData,
        private PublicSeasonData $seasonData,
    ) {}

    public function show(Season $season, SeoMetadata $seoMetadata): Response
    {
        $events = $season->events()
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
            ->with([
                'location:id,name,city',
                'season:id,name,slug',
                'coverImage:id,disk,path,alt_text',
            ])
            ->oldest('starts_at')
            ->oldest('id')
            ->get();

        abort_if($events->isEmpty(), 404);

        $seasonSummary = $this->seasonData->summary($season);

        return Inertia::render('public/season-show', [
            'season' => [
                ...$seasonSummary,
                'events' => $events
                    ->map(fn (Event $event): array => $this->eventData->summary($event))
                    ->values()
                    ->all(),
            ],
            'seo' => $seoMetadata->forPage('season', [
                'title' => $season->name,
                'description' => "Bekijk het seizoensticket en alle events van {$season->name}.",
                'canonical_path' => route('seasons.show', ['season' => $season], false),
            ]),
        ]);
    }
}
