<?php

namespace Database\Factories;

use App\Enums\EventRegistrationStatus;
use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Models\Event;
use App\Models\Location;
use App\Models\MediaAsset;
use App\Models\Season;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);
        $startsAt = fake()->dateTimeBetween('+1 week', '+6 months');

        return [
            'location_id' => Location::factory(),
            'season_id' => null,
            'cover_image_id' => null,
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numerify('###'),
            'content' => fake()->paragraphs(3, true),
            'starts_at' => $startsAt,
            'ends_at' => (clone $startsAt)->modify('+4 hours'),
            'published_at' => null,
            'status' => EventStatus::Draft,
            'type' => EventType::Other,
            'price_cents' => fake()->numberBetween(0, 5000),
            'capacity' => fake()->numberBetween(12, 16),
            'registration_opens_at' => (clone $startsAt)->modify('-1 month'),
            'registration_deadline_at' => (clone $startsAt)->modify('-1 day')->setTime(23, 59),
            'registration_status' => EventRegistrationStatus::Closed,
            'registration_url' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => EventStatus::Published,
            'published_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (): array => [
            'status' => EventStatus::Cancelled,
        ]);
    }

    public function training(): static
    {
        return $this->state(fn (): array => [
            'type' => EventType::Training,
        ]);
    }

    public function withCoverImage(): static
    {
        return $this->state(fn (): array => [
            'cover_image_id' => MediaAsset::factory(),
        ]);
    }

    public function inSeason(): static
    {
        return $this->state(fn (): array => [
            'season_id' => Season::factory(),
        ]);
    }
}
