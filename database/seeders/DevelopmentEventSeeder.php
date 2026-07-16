<?php

namespace Database\Seeders;

use App\Enums\EventRegistrationStatus;
use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Enums\LocationEnvironment;
use App\Models\Event;
use App\Models\Location;
use App\Models\MediaAsset;
use App\Models\Season;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class DevelopmentEventSeeder extends Seeder
{
    public const EVENT_SLUG_PREFIX = 'dds-demo-';

    public const LOCATION_SLUG_PREFIX = 'dds-demo-location-';

    public const MEDIA_PATH_PREFIX = 'demo/events/';

    public const SEASON_NAME = 'DDS demo seizoen';

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
    ];

    /** @var list<string> */
    public const LOCATION_SLUGS = [
        'dds-demo-location-sportpaleis-alkmaar',
        'dds-demo-location-koggenhal',
        'dds-demo-location-sporthal-oosterhout',
    ];

    /** @var list<string> */
    public const MEDIA_PATHS = [
        'demo/events/pilot-at-training.jpg',
        'demo/events/race-control.jpg',
        'demo/events/indoor-track.jpg',
    ];

    public function run(): void
    {
        $this->ensureDevelopmentEnvironment();

        $referenceDate = CarbonImmutable::today('Europe/Amsterdam');

        DB::transaction(function () use ($referenceDate): void {
            $covers = $this->seedCovers();
            $locations = $this->seedLocations();
            $season = Season::query()->updateOrCreate(
                ['name' => self::SEASON_NAME],
                Season::factory()->make([
                    'name' => self::SEASON_NAME,
                    'price_cents' => 9000,
                    'ticket_capacity' => null,
                ])->toArray(),
            );

            $this->seedEvents($referenceDate, $locations, $covers, $season);
        });
    }

    public function reset(): int
    {
        $this->ensureDevelopmentEnvironment();

        /** @var array{deletedEvents: int, mediaPaths: list<string>} $result */
        $result = DB::transaction(function (): array {
            $deletedEvents = Event::query()
                ->whereIn('slug', self::EVENT_SLUGS)
                ->delete();

            $mediaAssetsQuery = MediaAsset::query()
                ->where('disk', 'public')
                ->whereIn('path', self::MEDIA_PATHS)
                ->whereDoesntHave('coverEvents')
                ->whereDoesntHave('coverLocations');

            if (Schema::hasTable('articles')) {
                $mediaAssetsQuery->whereDoesntHave('coverArticles');
            }

            $mediaAssets = $mediaAssetsQuery->get(['id', 'path']);

            MediaAsset::query()->whereKey($mediaAssets->modelKeys())->delete();

            Location::query()
                ->whereIn('slug', self::LOCATION_SLUGS)
                ->whereDoesntHave('events')
                ->delete();

            Season::query()
                ->where('name', self::SEASON_NAME)
                ->whereDoesntHave('events')
                ->delete();

            return [
                'deletedEvents' => $deletedEvents,
                'mediaPaths' => $mediaAssets->pluck('path')->all(),
            ];
        });

        if ($result['mediaPaths'] !== [] && ! Storage::disk('public')->delete($result['mediaPaths'])) {
            throw new RuntimeException('Niet alle ongebruikte demo-afbeeldingen konden worden verwijderd.');
        }

        return $result['deletedEvents'];
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
                'path' => self::MEDIA_PATHS[0],
                'filename' => 'pilot-at-training.jpg',
                'alt' => 'FPV-piloot tijdens een indoortraining van Dutch Drone Squad',
            ],
            'race' => [
                'source' => public_path('images/dds/racing/race-control.jpg'),
                'path' => self::MEDIA_PATHS[1],
                'filename' => 'race-control.jpg',
                'alt' => 'Race control tijdens een wedstrijd van Dutch Drone Squad',
            ],
            'track' => [
                'source' => public_path('images/dds/racing/indoor-track.jpg'),
                'path' => self::MEDIA_PATHS[2],
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

            $path = $fixture['path'];

            if (! Storage::disk('public')->put($path, File::get($fixture['source']))) {
                throw new RuntimeException("Demo-afbeelding kon niet worden opgeslagen: {$path}");
            }

            $covers[$key] = MediaAsset::query()->updateOrCreate(
                ['disk' => 'public', 'path' => $path],
                MediaAsset::factory()->make([
                    'disk' => 'public',
                    'path' => $path,
                    'original_filename' => $fixture['filename'],
                    'mime_type' => 'image/jpeg',
                    'size_bytes' => File::size($fixture['source']),
                    'width' => $dimensions[0],
                    'height' => $dimensions[1],
                    'alt_text' => ['nl' => $fixture['alt']],
                ])->toArray(),
            );
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
                    $this->scheduledDate($nextSunday, 0, 18),
                ),
                'slug' => self::EVENT_SLUGS[0],
                'content' => "Op deze zondagavond bouwen we in het Sportpaleis een nieuw indoorparcours op. Je vliegt vrije heats, terwijl de racetimers je rondetijden registreren voor FPV Scores.\n\nNeem je eigen quad, goggles, radio, voldoende geladen accu’s en eventueel een stekkerdoos mee. Zet je VTX op 25 mW en gebruik bij digitaal vliegen een bitrate van 25 Mbit. We waarderen hulp bij het opbouwen en afbreken.",
                'starts_at' => $this->scheduledDate($nextSunday, 0, 18),
                'ends_at' => $this->scheduledDate($nextSunday, 0, 21),
                'location_id' => $locations['alkmaar']->id,
                'season_id' => $season->id,
                'cover_image_id' => $covers['training']->id,
                'status' => EventStatus::Published,
                'type' => EventType::Training,
                'price_cents' => 1500,
                'capacity' => 16,
                'registration_opens_at' => $this->registrationOpensAt($this->scheduledDate($nextSunday, 0, 18)),
                'registration_deadline_at' => $this->registrationDeadlineAt($this->scheduledDate($nextSunday, 0, 18)),
                'registration_status' => EventRegistrationStatus::Open,
                'registration_url' => $registrationUrl,
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
                'content' => "Een rustige oefenavond voor beginners en piloten die door obstakels willen leren vliegen. Als de zaal wordt verduisterd, lichten de vaste UV-installatie, het parcours en drones met leds op.\n\nDe reguliere plaatsen zijn vergeven. Aanmelden voor de wachtlijst is nog mogelijk; deze avond gaat door bij voldoende belangstelling.",
                'starts_at' => $this->eventDate($nextSunday, 2, 18),
                'ends_at' => $this->eventDate($nextSunday, 2, 21),
                'location_id' => $locations['de-goorn']->id,
                'season_id' => null,
                'cover_image_id' => $covers['track']->id,
                'status' => EventStatus::Published,
                'type' => EventType::Training,
                'price_cents' => null,
                'capacity' => 12,
                'registration_opens_at' => $this->registrationOpensAt($this->eventDate($nextSunday, 2, 18)),
                'registration_deadline_at' => $this->registrationDeadlineAt($this->eventDate($nextSunday, 2, 18)),
                'registration_status' => EventRegistrationStatus::Waitlist,
                'registration_url' => $registrationUrl,
            ],
            [
                'factory' => Event::factory()->published()->training(),
                'title' => 'FPV-kennismakingsavond',
                'slug' => self::EVENT_SLUGS[3],
                'content' => null,
                'starts_at' => $this->eventDate($nextSunday, 4, 18),
                'ends_at' => $this->eventDate($nextSunday, 4, 21),
                'location_id' => $locations['de-goorn']->id,
                'season_id' => null,
                'cover_image_id' => null,
                'status' => EventStatus::Published,
                'type' => EventType::Training,
                'price_cents' => 0,
                'capacity' => 10,
                'registration_opens_at' => $this->registrationOpensAt($this->eventDate($nextSunday, 4, 18)),
                'registration_deadline_at' => $this->registrationDeadlineAt($this->eventDate($nextSunday, 4, 18)),
                'registration_status' => EventRegistrationStatus::Full,
                'registration_url' => null,
            ],
            [
                'factory' => Event::factory()->published(),
                'title' => 'Indoor FPV-clubrace',
                'slug' => self::EVENT_SLUGS[4],
                'content' => 'Omdat het Sportpaleis deze dag niet beschikbaar is, wijkt deze compacte clubrace eenmalig uit naar Sporthal Oosterhout. We bouwen een technisch parcours op en gebruiken de DDS-racetimers. De inschrijving is gesloten; bezoekers zijn welkom.',
                'starts_at' => $this->scheduledDate($nextSunday, 4, 10),
                'ends_at' => $this->scheduledDate($nextSunday, 4, 16, 30),
                'location_id' => $locations['oosterhout']->id,
                'season_id' => null,
                'cover_image_id' => null,
                'status' => EventStatus::Published,
                'type' => EventType::Race,
                'price_cents' => 1750,
                'capacity' => 32,
                'registration_opens_at' => $this->registrationOpensAt($this->scheduledDate($nextSunday, 4, 10)),
                'registration_deadline_at' => $this->registrationDeadlineAt($this->scheduledDate($nextSunday, 4, 10)),
                'registration_status' => EventRegistrationStatus::Closed,
                'registration_url' => null,
            ],
            [
                'factory' => Event::factory()->cancelled()->training(),
                'title' => $this->recurringEventTitle(
                    'FPV uitwijkavond',
                    $this->scheduledDate($nextSunday, 5, 18),
                ),
                'slug' => self::EVENT_SLUGS[5],
                'content' => 'Deze uitwijkvliegavond was gepland omdat het Sportpaleis niet beschikbaar was, maar gaat niet door. Houd de agenda in de gaten voor een volgende zondagavond.',
                'starts_at' => $this->scheduledDate($nextSunday, 5, 18),
                'ends_at' => $this->scheduledDate($nextSunday, 5, 21),
                'location_id' => $locations['oosterhout']->id,
                'season_id' => null,
                'cover_image_id' => $covers['training']->id,
                'status' => EventStatus::Cancelled,
                'type' => EventType::Training,
                'price_cents' => 1500,
                'capacity' => 16,
                'registration_opens_at' => $this->registrationOpensAt($this->scheduledDate($nextSunday, 5, 18)),
                'registration_deadline_at' => $this->registrationDeadlineAt($this->scheduledDate($nextSunday, 5, 18)),
                'registration_status' => EventRegistrationStatus::Closed,
                'registration_url' => null,
            ],
            [
                'factory' => Event::factory()->published(),
                'title' => 'Workshop FPV-racevoorbereiding',
                'slug' => self::EVENT_SLUGS[6],
                'content' => 'Een technische workshop over betrouwbare builds, VTX-instellingen, 25 mW zendvermogen, digitale bitrate, kanaalindeling en racevoorbereiding voor het volgende indoorseizoen.',
                'starts_at' => $this->eventDate($referenceDate, 365, 13),
                'ends_at' => $this->eventDate($referenceDate, 365, 17),
                'location_id' => $locations['alkmaar']->id,
                'season_id' => null,
                'cover_image_id' => $covers['track']->id,
                'status' => EventStatus::Published,
                'type' => EventType::Workshop,
                'price_cents' => 1250,
                'capacity' => 24,
                'registration_opens_at' => $this->registrationOpensAt($this->eventDate($referenceDate, 365, 13)),
                'registration_deadline_at' => $this->registrationDeadlineAt($this->eventDate($referenceDate, 365, 13)),
                'registration_status' => EventRegistrationStatus::Closed,
                'registration_url' => null,
            ],
        ];

        foreach ($fixtures as $fixture) {
            $factory = $fixture['factory'];
            unset($fixture['factory']);

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

    private function eventDate(
        CarbonImmutable $referenceDate,
        int $days,
        int $hour,
        int $minute = 0,
    ): CarbonImmutable {
        return $referenceDate->addDays($days)->setTime($hour, $minute)->utc();
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
            ->subWeeks(2)
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
        $monthAndYear = $startsAt
            ->setTimezone('Europe/Amsterdam')
            ->locale('nl')
            ->translatedFormat('F Y');

        return "{$name} - {$monthAndYear}";
    }

    private function ensureDevelopmentEnvironment(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('De DDS demo-events mogen alleen lokaal worden beheerd.');
        }
    }
}
