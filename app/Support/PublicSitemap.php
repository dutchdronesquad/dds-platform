<?php

namespace App\Support;

use App\Models\Article;
use App\Models\Event;
use App\Models\Location;
use App\Models\Season;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

final class PublicSitemap
{
    /** @var list<string> */
    private const array STATIC_ROUTE_NAMES = [
        'home',
        'events.index',
        'projects.index',
        'news.index',
        'locations.index',
        'getting_started.index',
        'about',
        'house_rules',
        'media',
        'partners',
        'contact',
    ];

    public function build(): Sitemap
    {
        $sitemap = Sitemap::create();

        foreach (self::STATIC_ROUTE_NAMES as $routeName) {
            $sitemap->add(route($routeName));
        }

        foreach (GettingStartedGuides::fromConfig()->all() as $guide) {
            $sitemap->add(
                Url::create(route('getting_started.show', ['guide' => $guide->slug]))
                    ->setLastModificationDate(new \DateTimeImmutable($guide->reviewedAt)),
            );
        }

        Article::query()
            ->select(['id', 'slug', 'updated_at'])
            ->publiclyVisible()
            ->oldest('id')
            ->each(function (Article $article) use ($sitemap): void {
                $sitemap->add($this->modelUrl(
                    route('news.show', ['article' => $article]),
                    $article,
                ));
            });

        Event::query()
            ->select(['id', 'slug', 'updated_at'])
            ->publiclyVisible()
            ->oldest('id')
            ->each(function (Event $event) use ($sitemap): void {
                $sitemap->add($this->modelUrl(
                    route('events.show', ['event' => $event]),
                    $event,
                ));
            });

        Location::query()
            ->select(['id', 'slug', 'updated_at'])
            ->oldest('id')
            ->each(function (Location $location) use ($sitemap): void {
                $sitemap->add($this->modelUrl(
                    route('locations.show', ['location' => $location]),
                    $location,
                ));
            });

        Season::query()
            ->select(['id', 'slug', 'updated_at'])
            ->whereIn('id', Event::query()
                ->select('season_id')
                ->distinct()
                ->whereNotNull('season_id')
                ->publiclyVisible())
            ->oldest('id')
            ->each(function (Season $season) use ($sitemap): void {
                $sitemap->add($this->modelUrl(
                    route('seasons.show', ['season' => $season]),
                    $season,
                ));
            });

        return $sitemap;
    }

    private function modelUrl(string $url, Model $model): Url
    {
        $sitemapUrl = Url::create($url);
        $updatedAt = $model->getAttribute($model->getUpdatedAtColumn());

        if ($updatedAt instanceof DateTimeInterface) {
            $sitemapUrl->setLastModificationDate($updatedAt);
        }

        return $sitemapUrl;
    }
}
