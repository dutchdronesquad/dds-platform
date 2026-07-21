<?php

namespace Database\Seeders;

use App\Enums\EventRegistrationStatus;
use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Enums\LocationEnvironment;
use App\Enums\SeasonTicketSalesState;
use App\Models\Event;
use App\Models\Location;
use App\Models\MediaAsset;
use App\Models\Season;
use App\Models\SeasonTicket;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

final class DevelopmentEventSeeder extends Seeder
{
    private const EVENT_INTERVAL_WEEKS = 4;

    private const SEASON_EVENT_COUNT = 8;

    public const EVENT_SLUG_PREFIX = 'dds-demo-';

    public const LOCATION_SLUG_PREFIX = 'dds-demo-location-';

    public const SEASON_SLUG = 'dds-demo-seizoen';

    /**
     * @var list<string>
     */
    public const EVENT_SLUGS = [
        'dds-demo-indoor-training-laatste-plaatsen',
        'dds-demo-dutch-drone-racing-championship',
        'dds-demo-training-wachtlijst',
        'dds-demo-gratis-training-vol',
        'dds-demo-club-race-inschrijving-gesloten',
        'dds-demo-geannuleerde-zomer-race',
        'dds-demo-workshop-volgend-seizoen',
        'dds-demo-vliegavond-augustus',
        'dds-demo-vliegavond-september',
        'dds-demo-vliegavond-oktober-los-ticket',
    ];

    /** @var list<string> */
    public const LOCATION_SLUGS = [
        'dds-demo-location-sportpaleis-alkmaar',
        'dds-demo-location-koggenhal',
        'dds-demo-location-sporthal-oosterhout',
    ];

    /** @var list<string> */
    public const MEDIA_FIXTURE_KEYS = [
        'training',
        'race',
        'track',
    ];

    public function run(): void
    {
        $this->ensureDevelopmentEnvironment();

        $referenceDate = CarbonImmutable::now('Europe/Amsterdam');

        DB::transaction(function () use ($referenceDate): void {
            $covers = $this->seedCovers();
            $locations = $this->seedLocations();
            $seasonStart = $this->demoSeasonStart($referenceDate);
            $season = Season::query()->updateOrCreate(
                ['slug' => self::SEASON_SLUG],
                Season::factory()->make([
                    'name' => $this->demoSeasonName($seasonStart),
                    'slug' => self::SEASON_SLUG,
                ])->toArray(),
            );

            $this->seedEvents(
                $referenceDate,
                $seasonStart,
                $locations,
                $covers,
                $season,
            );
            $this->seedSeasonTicket($referenceDate, $season);
        });
    }

    public function reset(): int
    {
        $this->ensureDevelopmentEnvironment();

        return DB::transaction(function (): int {
            $deletedEvents = Event::query()
                ->whereIn('slug', self::EVENT_SLUGS)
                ->delete();

            $mediaAssetsQuery = MediaAsset::query()
                ->whereHas('media', fn ($query) => $query
                    ->whereIn('custom_properties->development_fixture', self::MEDIA_FIXTURE_KEYS))
                ->whereDoesntHave('coverEvents')
                ->whereDoesntHave('coverLocations');

            if (Schema::hasTable('articles')) {
                $mediaAssetsQuery->whereDoesntHave('coverArticles');
            }

            $mediaAssetsQuery->get()->each->delete();

            Location::query()
                ->whereIn('slug', self::LOCATION_SLUGS)
                ->whereDoesntHave('events')
                ->delete();

            Season::query()
                ->where('slug', self::SEASON_SLUG)
                ->whereDoesntHave('events')
                ->delete();

            return $deletedEvents;
        });
    }

    /**
     * @return array<string, MediaAsset>
     */
    private function seedCovers(): array
    {
        $covers = [];
        $fixtures = [
            'training' => [
                'source' => public_path('images/dds/racing/pilot-at-training.jpg'),
                'filename' => 'pilot-at-training.jpg',
                'alt' => 'FPV-piloot tijdens een indoortraining van Dutch Drone Squad',
            ],
            'race' => [
                'source' => public_path('images/dds/racing/race-control.jpg'),
                'filename' => 'race-control.jpg',
                'alt' => 'Race control tijdens een wedstrijd van Dutch Drone Squad',
            ],
            'track' => [
                'source' => public_path('images/dds/racing/indoor-track.jpg'),
                'filename' => 'indoor-track.jpg',
                'alt' => 'Indoor FPV-raceparcours van Dutch Drone Squad',
            ],
        ];

        foreach ($fixtures as $key => $fixture) {
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
                ->first();

            if (! $mediaAsset instanceof MediaAsset) {
                $mediaAsset = MediaAsset::query()->create();
            }

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
     * @return array<string, Location>
     */
    private function seedLocations(): array
    {
        $locations = [];
        $fixtures = [
            'alkmaar' => [
                'name' => 'Sportpaleis Alkmaar',
                'slug' => self::LOCATION_SLUGS[0],
                'description' => ['nl' => 'Ruime indoor wielerbaan waar DDS op zondagavond een wisselend FPV-parcours opbouwt en rondetijden meet met racetimers.'],
                'street' => 'Terborchlaan',
                'house_number' => '200',
                'postal_code' => '1816 LE',
                'city' => 'Alkmaar',
                'floor_size_square_metres' => 2000,
                'ceiling_height_metres' => 11.0,
                'facilities' => ['parking', 'power', 'toilets', 'tables_and_chairs'],
                'latitude' => 52.6317600,
                'longitude' => 4.7336300,
            ],
            'de-goorn' => [
                'name' => 'Sportcentrum Koggenhal',
                'slug' => self::LOCATION_SLUGS[1],
                'description' => ['nl' => 'Sporthal met vaste blacklightinstallatie voor beginners en piloten die rustig door obstakels willen leren vliegen.'],
                'street' => 'Dwingel',
                'house_number' => '4',
                'postal_code' => '1648 JM',
                'city' => 'De Goorn',
                'floor_size_square_metres' => 1350,
                'ceiling_height_metres' => 9.0,
                'facilities' => ['parking', 'power', 'toilets'],
                'latitude' => 52.6279700,
                'longitude' => 4.9462500,
            ],
            'oosterhout' => [
                'name' => 'Sporthal Oosterhout',
                'slug' => self::LOCATION_SLUGS[2],
                'description' => ['nl' => 'Alkmaarse uitwijklocatie die DDS alleen gebruikt wanneer het Sportpaleis niet beschikbaar is. Bij gebruik bouwen we een indoorparcours op en meten we rondetijden met de DDS-racetimers.'],
                'street' => 'Vondelstraat',
                'house_number' => '35',
                'postal_code' => '1813 AA',
                'city' => 'Alkmaar',
                'floor_size_square_metres' => 1000,
                'ceiling_height_metres' => 9.0,
                'facilities' => ['parking', 'power', 'toilets'],
                'latitude' => 52.6213000,
                'longitude' => 4.7514000,
            ],
        ];

        foreach ($fixtures as $key => $fixture) {
            $locations[$key] = Location::query()->updateOrCreate(
                ['slug' => $fixture['slug']],
                Location::factory()->make([
                    'cover_image_id' => null,
                    ...$fixture,
                    'country_code' => 'NL',
                    'environment' => LocationEnvironment::Indoor,
                    'website_url' => null,
                ])->toArray(),
            );
        }

        return $locations;
    }

    /**
     * @param  array<string, Location>  $locations
     * @param  array<string, MediaAsset>  $covers
     */
    private function seedEvents(
        CarbonImmutable $referenceDate,
        CarbonImmutable $seasonStart,
        array $locations,
        array $covers,
        Season $season,
    ): void {
        $publishedAt = $referenceDate->subDay()->setTime(9, 0)->utc();
        $registrationUrl = 'https://example.com/dds-demo-registration';
        $nextSunday = $referenceDate->next(CarbonInterface::SUNDAY);
        $fixtures = [
            [
                'factory' => Event::factory()->published()->training(),
                'title' => $this->recurringEventTitle(
                    'FPV vliegavond',
                    $this->demoSeasonDate($seasonStart, 0, 18),
                ),
                'slug' => self::EVENT_SLUGS[0],
                'content' => "Op deze zondagavond bouwen we in het Sportpaleis een nieuw indoorparcours op. Je vliegt vrije heats, terwijl de racetimers je rondetijden registreren voor FPV Scores.\n\nNeem je eigen quad, goggles, radio, voldoende geladen accu’s en eventueel een stekkerdoos mee. Zet je VTX op 25 mW en gebruik bij digitaal vliegen een bitrate van 25 Mbit. We waarderen hulp bij het opbouwen en afbreken.",
                'starts_at' => $this->demoSeasonDate($seasonStart, 0, 18),
                'ends_at' => $this->demoSeasonDate($seasonStart, 0, 21),
                'location_id' => $locations['alkmaar']->id,
                'season_id' => $season->id,
                'cover_image_id' => $covers['training']->id,
                'status' => EventStatus::Published,
                'type' => EventType::Training,
                'price_cents' => null,
                'capacity' => 16,
                'registration_opens_at' => null,
                'registration_deadline_at' => null,
                'registration_status' => EventRegistrationStatus::Closed,
                'registration_url' => null,
            ],
            [
                'factory' => Event::factory()->published(),
                'title' => 'Indoor FPV-clubrace met kwalificaties en finales',
                'slug' => self::EVENT_SLUGS[1],
                'content' => "Een volledige racedag op een groot indoorparcours met kwalificaties, knock-outs en finales. De racetimers ondersteunen zowel analoge als digitale videosystemen.\n\nDeze dag is gericht op piloten die zelfstandig een parcours kunnen vliegen en hun materiaal raceklaar kunnen houden.",
                'starts_at' => $this->scheduledDate($nextSunday, 1, 9, 30),
                'ends_at' => $this->scheduledDate($nextSunday, 1, 18),
                'location_id' => $locations['alkmaar']->id,
                'season_id' => null,
                'cover_image_id' => $covers['race']->id,
                'status' => EventStatus::Published,
                'type' => EventType::Race,
                'price_cents' => 2500,
                'capacity' => 32,
                'registration_opens_at' => $this->registrationOpensAt($this->scheduledDate($nextSunday, 1, 9, 30)),
                'registration_deadline_at' => $this->registrationDeadlineAt($this->scheduledDate($nextSunday, 1, 9, 30)),
                'registration_status' => EventRegistrationStatus::Open,
                'registration_url' => $registrationUrl,
            ],
            [
                'factory' => Event::factory()->published()->training(),
                'title' => 'Blacklight FPV-oefenavond',
                'slug' => self::EVENT_SLUGS[2],
                'content' => "Een rustige oefenavond voor beginners en piloten die door obstakels willen leren vliegen. Als de zaal wordt verduisterd, lichten de vaste UV-installatie, het parcours en drones met leds op.\n\nDeze avond maakt deel uit van het doorlopende demo-seizoen en is ook als losse vliegavond te boeken zodra de inschrijving opent.",
                'starts_at' => $this->demoSeasonDate($seasonStart, 1, 18),
                'ends_at' => $this->demoSeasonDate($seasonStart, 1, 21),
                'location_id' => $locations['de-goorn']->id,
                'season_id' => $season->id,
                'cover_image_id' => $covers['track']->id,
                'status' => EventStatus::Published,
                'type' => EventType::Training,
                'price_cents' => null,
                'capacity' => 12,
                'registration_opens_at' => null,
                'registration_deadline_at' => null,
                'registration_status' => EventRegistrationStatus::Closed,
                'registration_url' => null,
            ],
            [
                'factory' => Event::factory()->published()->training(),
                'title' => 'FPV-kennismakingsavond',
                'slug' => self::EVENT_SLUGS[3],
                'content' => null,
                'starts_at' => $this->demoSeasonDate($seasonStart, 2, 18),
                'ends_at' => $this->demoSeasonDate($seasonStart, 2, 21),
                'location_id' => $locations['de-goorn']->id,
                'season_id' => $season->id,
                'cover_image_id' => null,
                'status' => EventStatus::Published,
                'type' => EventType::Training,
                'price_cents' => null,
                'capacity' => 10,
                'registration_opens_at' => null,
                'registration_deadline_at' => null,
                'registration_status' => EventRegistrationStatus::Closed,
                'registration_url' => null,
            ],
            [
                'factory' => Event::factory()->published()->training(),
                'title' => $this->recurringEventTitle(
                    'FPV vliegavond',
                    $this->demoSeasonDate($seasonStart, 3, 18),
                ),
                'slug' => self::EVENT_SLUGS[4],
                'content' => 'Een indoor vliegavond met een toegankelijk parcours en vrije heats voor alle deelnemers aan het DDS-demo-seizoen.',
                'starts_at' => $this->demoSeasonDate($seasonStart, 3, 18),
                'ends_at' => $this->demoSeasonDate($seasonStart, 3, 21),
                'location_id' => $locations['alkmaar']->id,
                'season_id' => $season->id,
                'cover_image_id' => $covers['training']->id,
                'status' => EventStatus::Published,
                'type' => EventType::Training,
                'price_cents' => null,
                'capacity' => 16,
                'registration_opens_at' => null,
                'registration_deadline_at' => null,
                'registration_status' => EventRegistrationStatus::Closed,
                'registration_url' => null,
            ],
            [
                'factory' => Event::factory()->cancelled()->training(),
                'title' => $this->recurringEventTitle(
                    'FPV uitwijkavond',
                    $this->demoSeasonDate($seasonStart, 4, 18),
                ),
                'slug' => self::EVENT_SLUGS[5],
                'content' => 'Deze uitwijkvliegavond was gepland omdat het Sportpaleis niet beschikbaar was, maar gaat niet door. Houd de agenda in de gaten voor een volgende zondagavond.',
                'starts_at' => $this->demoSeasonDate($seasonStart, 4, 18),
                'ends_at' => $this->demoSeasonDate($seasonStart, 4, 21),
                'location_id' => $locations['oosterhout']->id,
                'season_id' => $season->id,
                'cover_image_id' => $covers['training']->id,
                'status' => EventStatus::Cancelled,
                'type' => EventType::Training,
                'price_cents' => null,
                'capacity' => 16,
                'registration_opens_at' => null,
                'registration_deadline_at' => null,
                'registration_status' => EventRegistrationStatus::Closed,
                'registration_url' => null,
            ],
            [
                'factory' => Event::factory()->published(),
                'title' => 'Workshop FPV-racevoorbereiding',
                'slug' => self::EVENT_SLUGS[6],
                'content' => 'Een technische workshop over betrouwbare builds, VTX-instellingen, 25 mW zendvermogen, digitale bitrate, kanaalindeling en racevoorbereiding voor het volgende indoorseizoen.',
                'starts_at' => $this->scheduledDate($nextSunday, 16, 13),
                'ends_at' => $this->scheduledDate($nextSunday, 16, 17),
                'location_id' => $locations['alkmaar']->id,
                'season_id' => null,
                'cover_image_id' => $covers['track']->id,
                'status' => EventStatus::Published,
                'type' => EventType::Workshop,
                'price_cents' => 1250,
                'capacity' => 24,
                'registration_opens_at' => $this->registrationOpensAt($this->scheduledDate($nextSunday, 16, 13)),
                'registration_deadline_at' => $this->registrationDeadlineAt($this->scheduledDate($nextSunday, 16, 13)),
                'registration_status' => EventRegistrationStatus::Closed,
                'registration_url' => null,
            ],
            [
                'factory' => Event::factory()->published()->training(),
                'title' => $this->recurringEventTitle(
                    'FPV vliegavond',
                    $this->demoSeasonDate($seasonStart, 5, 18),
                ),
                'slug' => self::EVENT_SLUGS[7],
                'content' => 'Een volgende vliegavond binnen het DDS-seizoen. We bouwen een technisch maar toegankelijk indoorparcours op met ruimte voor vrije heats, rondetijden en begeleiding voor nieuwe piloten.',
                'starts_at' => $this->demoSeasonDate($seasonStart, 5, 18),
                'ends_at' => $this->demoSeasonDate($seasonStart, 5, 21),
                'location_id' => $locations['alkmaar']->id,
                'season_id' => $season->id,
                'cover_image_id' => $covers['training']->id,
                'status' => EventStatus::Published,
                'type' => EventType::Training,
                'price_cents' => null,
                'capacity' => 16,
                'registration_opens_at' => null,
                'registration_deadline_at' => null,
                'registration_status' => EventRegistrationStatus::Closed,
                'registration_url' => null,
            ],
            [
                'factory' => Event::factory()->published()->training(),
                'title' => $this->recurringEventTitle(
                    'FPV vliegavond',
                    $this->demoSeasonDate($seasonStart, 6, 18),
                ),
                'slug' => self::EVENT_SLUGS[8],
                'content' => 'Een volgende vliegavond in het demo-seizoen met vrije heats, rondetijden en ruimte voor begeleiding van nieuwe piloten.',
                'starts_at' => $this->demoSeasonDate($seasonStart, 6, 18),
                'ends_at' => $this->demoSeasonDate($seasonStart, 6, 21),
                'location_id' => $locations['alkmaar']->id,
                'season_id' => $season->id,
                'cover_image_id' => $covers['track']->id,
                'status' => EventStatus::Published,
                'type' => EventType::Training,
                'price_cents' => null,
                'capacity' => 16,
                'registration_opens_at' => null,
                'registration_deadline_at' => null,
                'registration_status' => EventRegistrationStatus::Closed,
                'registration_url' => null,
            ],
            [
                'factory' => Event::factory()->published()->training(),
                'title' => $this->recurringEventTitle(
                    'FPV vliegavond',
                    $this->demoSeasonDate($seasonStart, 7, 18),
                ),
                'slug' => self::EVENT_SLUGS[9],
                'content' => 'De afsluitende vliegavond van het DDS-indoorseizoen. We combineren vrije heats met een ontspannen seizoensafsluiting en bouwen daarna samen het parcours af.',
                'starts_at' => $this->demoSeasonDate($seasonStart, 7, 18),
                'ends_at' => $this->demoSeasonDate($seasonStart, 7, 21),
                'location_id' => $locations['alkmaar']->id,
                'season_id' => $season->id,
                'cover_image_id' => $covers['training']->id,
                'status' => EventStatus::Published,
                'type' => EventType::Training,
                'price_cents' => null,
                'capacity' => 16,
                'registration_opens_at' => null,
                'registration_deadline_at' => null,
                'registration_status' => EventRegistrationStatus::Closed,
                'registration_url' => null,
            ],
        ];

        foreach ($fixtures as $fixture) {
            $factory = $fixture['factory'];
            unset($fixture['factory']);

            if ($fixture['season_id'] === $season->id) {
                $fixture['price_cents'] = 1500;

                if ($fixture['status'] === EventStatus::Published) {
                    $fixture['registration_opens_at'] = $this->registrationOpensAt(
                        $fixture['starts_at'],
                    );
                    $fixture['registration_deadline_at'] = $this->registrationDeadlineAt(
                        $fixture['starts_at'],
                    );
                }
            }

            if (
                $fixture['status'] === EventStatus::Published
                && $fixture['registration_opens_at'] instanceof CarbonImmutable
                && $fixture['registration_deadline_at'] instanceof CarbonImmutable
            ) {
                $registrationIsOpen = $referenceDate->betweenIncluded(
                    $fixture['registration_opens_at'],
                    $fixture['registration_deadline_at'],
                );
                $fixture['registration_status'] = $registrationIsOpen
                    ? EventRegistrationStatus::Open
                    : EventRegistrationStatus::Closed;
                $fixture['registration_url'] = $registrationIsOpen
                    ? $registrationUrl
                    : null;
            }

            $attributes = $factory->make([
                ...$fixture,
                'published_at' => $publishedAt,
            ])->toArray();

            Event::query()->updateOrCreate(
                ['slug' => $fixture['slug']],
                $attributes,
            );
        }
    }

    private function seedSeasonTicket(CarbonImmutable $referenceDate, Season $season): void
    {
        SeasonTicket::query()->updateOrCreate(
            ['season_id' => $season->id],
            SeasonTicket::factory()->available()->make([
                'season_id' => $season->id,
                'sales_state' => SeasonTicketSalesState::Available,
                'sales_opens_at' => $referenceDate->subMonth()->utc(),
                'sales_closes_at' => $referenceDate->addYear()->utc(),
                'registration_url' => 'https://example.com/dds-demo-season-ticket',
                'copy' => 'Met één seizoensticket neem je deel aan alle acht indoor vliegavonden in dit demo-seizoen.',
                'price_cents' => 9000,
                'capacity' => null,
            ])->toArray(),
        );
    }

    private function demoSeasonStart(CarbonImmutable $referenceDate): CarbonImmutable
    {
        return $referenceDate
            ->setTimezone('Europe/Amsterdam')
            ->subWeeks(self::EVENT_INTERVAL_WEEKS)
            ->next(CarbonInterface::SUNDAY)
            ->startOfDay();
    }

    private function demoSeasonName(CarbonImmutable $seasonStart): string
    {
        $seasonEnd = $seasonStart->addWeeks(
            (self::SEASON_EVENT_COUNT - 1) * self::EVENT_INTERVAL_WEEKS,
        );

        if ($seasonStart->year === $seasonEnd->year) {
            return "DDS indoorseizoen {$seasonStart->year}";
        }

        return "DDS indoorseizoen {$seasonStart->year}/{$seasonEnd->year}";
    }

    private function demoSeasonDate(
        CarbonImmutable $seasonStart,
        int $eventOffset,
        int $hour,
        int $minute = 0,
    ): CarbonImmutable {
        return $seasonStart
            ->addWeeks($eventOffset * self::EVENT_INTERVAL_WEEKS)
            ->setTime($hour, $minute)
            ->utc();
    }

    private function scheduledDate(
        CarbonImmutable $firstSunday,
        int $weeks,
        int $hour,
        int $minute = 0,
    ): CarbonImmutable {
        return $firstSunday->addWeeks($weeks)->setTime($hour, $minute)->utc();
    }

    private function registrationOpensAt(CarbonImmutable $startsAt): CarbonImmutable
    {
        return $startsAt
            ->setTimezone('Europe/Amsterdam')
            ->subWeek()
            ->startOfWeek(CarbonInterface::MONDAY)
            ->setTime(9, 0)
            ->utc();
    }

    private function registrationDeadlineAt(CarbonImmutable $startsAt): CarbonImmutable
    {
        return $startsAt
            ->setTimezone('Europe/Amsterdam')
            ->subDay()
            ->setTime(23, 59)
            ->utc();
    }

    private function recurringEventTitle(string $name, CarbonImmutable $startsAt): string
    {
        $localizedStartsAt = $startsAt->setTimezone('Europe/Amsterdam');
        $localizedStartsAt->locale('nl');
        $monthAndYear = $localizedStartsAt->translatedFormat('F Y');

        return "{$name} - {$monthAndYear}";
    }

    private function ensureDevelopmentEnvironment(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('De DDS demo-events mogen alleen lokaal worden beheerd.');
        }
    }
}
