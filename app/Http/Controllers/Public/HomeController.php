<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Event;
use App\Support\PublicArticleData;
use App\Support\PublicEventData;
use App\Support\PublicPartnerData;
use App\Support\SeoMetadata;
use Carbon\CarbonImmutable;
use Inertia\Inertia;
use Inertia\Response;
use IntlDateFormatter;

final class HomeController extends Controller
{
    public function __construct(
        private PublicArticleData $articleData,
        private PublicEventData $eventData,
        private PublicPartnerData $partnerData,
    ) {}

    public function __invoke(SeoMetadata $seoMetadata): Response
    {
        $upcomingEvents = Event::query()
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
            ->limit(3)
            ->get()
            ->map(fn (Event $event): array => $this->eventData->summary($event));

        return Inertia::render('welcome', [
            'latestNews' => $this->latestNews(),
            'partners' => $this->partnerData->forHomepage(),
            'seo' => $seoMetadata->forPage('home'),
            'upcomingEvents' => $upcomingEvents,
        ]);
    }

    /**
     * @return list<array{
     *     dateLabel: string,
     *     excerpt: string|null,
     *     href: string,
     *     image: array{src: string, alt: string},
     *     title: string,
     * }>
     */
    private function latestNews(): array
    {
        $articles = Article::query()
            ->select([
                'id',
                'author_id',
                'cover_image_id',
                'title',
                'slug',
                'content',
                'published_at',
                'status',
                'category',
            ])
            ->publiclyVisible()
            ->with(['coverImage:id,alt_text', 'coverImage.media'])
            ->orderByDesc('published_at')
            ->limit(3)
            ->get()
            ->map(function (Article $article): array {
                $summary = $this->articleData->summary($article);
                $publishedAt = $article->published_at;

                return [
                    'dateLabel' => $publishedAt instanceof CarbonImmutable
                        ? $this->formatDutchDate($publishedAt)
                        : '',
                    'excerpt' => $summary['excerpt'],
                    'href' => route('news.show', ['article' => $article->slug], false),
                    'image' => $summary['image'],
                    'title' => $summary['title'],
                ];
            })
            ->all();

        return array_values($articles);
    }

    private function formatDutchDate(CarbonImmutable $dateTime): string
    {
        $formatter = new IntlDateFormatter(
            'nl_NL',
            IntlDateFormatter::NONE,
            IntlDateFormatter::NONE,
            'Europe/Amsterdam',
            IntlDateFormatter::GREGORIAN,
            'd MMMM yyyy',
        );

        $formatted = $formatter->format($dateTime);

        return $formatted !== false ? $formatted : $dateTime->format('d-m-Y');
    }
}
