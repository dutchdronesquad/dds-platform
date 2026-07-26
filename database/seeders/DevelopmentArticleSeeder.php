<?php

namespace Database\Seeders;

use App\Enums\ArticleCategory;
use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\MediaAsset;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;

final class DevelopmentArticleSeeder extends Seeder
{
    /** @var list<string> */
    public const ARTICLE_SLUGS = [
        'dds-demo-lets-get-ready-indoor-seizoen-24-25',
        'dds-demo-here-we-go-indoor-seizoen-22-23',
        'dds-demo-bbq-fly-to-meat-you-2022',
    ];

    /** @var list<string> */
    public const MEDIA_FIXTURE_KEYS = [
        'article-season-2425',
        'article-season-2223',
        'article-bbq',
    ];

    public function run(): void
    {
        $this->ensureDevelopmentEnvironment();

        DB::transaction(function (): void {
            $covers = $this->seedCovers();

            foreach ($this->fixtures($covers) as $fixture) {
                Article::query()->updateOrCreate(
                    ['slug' => $fixture['slug']],
                    Article::factory()->make($fixture)->toArray(),
                );
            }
        });
    }

    public function reset(): int
    {
        $this->ensureDevelopmentEnvironment();

        return DB::transaction(function (): int {
            $deletedArticles = Article::query()
                ->whereIn('slug', self::ARTICLE_SLUGS)
                ->delete();

            MediaAsset::query()
                ->whereHas('media', fn ($query) => $query
                    ->whereIn('custom_properties->development_fixture', self::MEDIA_FIXTURE_KEYS))
                ->whereDoesntHave('coverArticles')
                ->get()
                ->each->delete();

            return $deletedArticles;
        });
    }

    /**
     * @return array<string, MediaAsset>
     */
    private function seedCovers(): array
    {
        $covers = [];
        $fixtures = [
            'article-season-2425' => [
                'source' => public_path('images/dds/racing/pilot-at-training.jpg'),
                'filename' => 'pilot-at-training.jpg',
                'alt' => 'FPV-piloot tijdens een indoor training van Dutch Drone Squad',
            ],
            'article-season-2223' => [
                'source' => public_path('images/dds/racing/indoor-track.jpg'),
                'filename' => 'indoor-track.jpg',
                'alt' => 'Indoor FPV-track in het Sportpaleis in Alkmaar',
            ],
            'article-bbq' => [
                'source' => public_path('images/dds/racing/training-community.jpg'),
                'filename' => 'training-community.jpg',
                'alt' => 'Piloten bij elkaar tijdens een event van Dutch Drone Squad',
            ],
        ];

        foreach ($fixtures as $key => $fixture) {
            $reusableMediaAsset = MediaAsset::query()
                ->whereHas('media', fn ($query) => $query
                    ->where('file_name', $fixture['filename']))
                ->first();

            if ($reusableMediaAsset instanceof MediaAsset) {
                $covers[$key] = $reusableMediaAsset->load('media');

                continue;
            }

            if (! File::isFile($fixture['source'])) {
                throw new RuntimeException("Demo-afbeelding ontbreekt: {$fixture['source']}");
            }

            $dimensions = getimagesize($fixture['source']);

            if ($dimensions === false) {
                throw new RuntimeException("Demo-afbeelding ontbreekt of is ongeldig: {$fixture['source']}");
            }

            $mediaAsset = MediaAsset::query()
                ->whereHas('media', fn ($query) => $query
                    ->where('custom_properties->development_fixture', $key))
                ->first() ?? MediaAsset::query()->create();

            $mediaAsset->update(['alt_text' => ['nl' => $fixture['alt']]]);
            $mediaAsset->clearMediaCollection(MediaAsset::COLLECTION);
            $mediaAsset
                ->addMedia($fixture['source'])
                ->preservingOriginal()
                ->usingName($fixture['filename'])
                ->withCustomProperties([
                    'width' => $dimensions[0],
                    'height' => $dimensions[1],
                    'development_fixture' => $key,
                ])
                ->toMediaCollection(MediaAsset::COLLECTION);

            $covers[$key] = $mediaAsset->load('media');
        }

        return $covers;
    }

    /**
     * @param  array<string, MediaAsset>  $covers
     * @return list<array<string, mixed>>
     */
    private function fixtures(array $covers): array
    {
        return [
            [
                'author_id' => null,
                'cover_image_id' => $covers['article-season-2425']->id,
                'title' => "Let's Get Ready! Indoor seizoen 24/25",
                'slug' => self::ARTICLE_SLUGS[0],
                'content' => "We kijken terug op een mooi vorig indoorseizoen en zijn enthousiast over wat er dit seizoen bij komt. Voor 24/25 plannen we zeven vliegavonden in het Sportpaleis Alkmaar, verspreid tussen oktober en het voorjaar.\n\nDe opzet blijft grotendeels hetzelfde: we bouwen op de zondagavonden een technisch maar toegankelijk parcours op, meten rondetijden met de racetimers en sluiten af met een gezamenlijke afbraak. Nieuw dit seizoen is dat we vaker wisselen tussen analoge en digitale videosystemen, zodat piloten met beide setups regelmatig aan bod komen.\n\nHoud de agenda in de gaten voor de exacte data en meld je aan zodra de inschrijving opent.",
                'published_at' => CarbonImmutable::create(2024, 9, 9, 9, 0, 0, 'Europe/Amsterdam'),
                'status' => ArticleStatus::Published,
                'category' => ArticleCategory::Announcement,
            ],
            [
                'author_id' => null,
                'cover_image_id' => $covers['article-season-2223']->id,
                'title' => 'Here we go! Indoor seizoen 22/23',
                'slug' => self::ARTICLE_SLUGS[1],
                'content' => "Een nieuw indoorseizoen in Alkmaar gaat van start, met een vernieuwde aanpak voor de events. In plaats van losse vliegavonden zonder vaste structuur, bouwen we dit seizoen naar een doorlopend programma toe met een vaste kern van deelnemers en een paar grotere racedagen.\n\nHet Sportpaleis blijft onze vaste thuisbasis. Elke vliegavond bouwen we een nieuw parcours op, zodat het voor herhalende bezoekers steeds anders blijft. Neem zoals gewoonlijk je eigen materiaal mee en meld je vooraf aan zodat we weten hoeveel piloten er komen.",
                'published_at' => CarbonImmutable::create(2022, 9, 4, 9, 0, 0, 'Europe/Amsterdam'),
                'status' => ArticleStatus::Published,
                'category' => ArticleCategory::Announcement,
            ],
            [
                'author_id' => null,
                'cover_image_id' => $covers['article-bbq']->id,
                'title' => 'BBQ: Fly to meat you 2022',
                'slug' => self::ARTICLE_SLUGS[2],
                'content' => "Als afsluiting van de zomer organiseerden we een gezamenlijke vliegmiddag met barbecue. Piloten konden vrij vliegen op een luchtig zomerparcours, terwijl er op de grill gezellig werd bijgekletst over de afgelopen buitenseizoen en de plannen voor de indoorwinter.\n\nBedankt aan iedereen die kwam vliegen, meebracht of hielp opruimen. Dit soort informele middagen blijven we graag organiseren tussen de reguliere events door.",
                'published_at' => CarbonImmutable::create(2022, 8, 13, 15, 0, 0, 'Europe/Amsterdam'),
                'status' => ArticleStatus::Published,
                'category' => ArticleCategory::Community,
            ],
        ];
    }

    private function ensureDevelopmentEnvironment(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('De DDS demo-artikelen mogen alleen lokaal worden beheerd.');
        }
    }
}
