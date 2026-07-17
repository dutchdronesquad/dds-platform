<?php

namespace Database\Factories;

use App\Enums\SeasonTicketSalesState;
use App\Models\Event;
use App\Models\Season;
use App\Models\SeasonTicket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SeasonTicket>
 */
class SeasonTicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'season_id' => Season::factory(),
            'sales_state' => SeasonTicketSalesState::Available,
            'sales_opens_at' => now()->subMonth(),
            'sales_closes_at' => now()->addMonth(),
            'registration_url' => fake()->url(),
            'copy' => fake()->paragraph(),
            'price_cents' => fake()->numberBetween(5_000, 15_000),
            'capacity' => fake()->numberBetween(5, 25),
        ];
    }

    public function notOffered(): static
    {
        return $this->state(fn (): array => [
            'sales_state' => SeasonTicketSalesState::NotOffered,
            'sales_opens_at' => null,
            'sales_closes_at' => null,
            'registration_url' => null,
            'price_cents' => null,
            'capacity' => null,
        ]);
    }

    public function comingSoon(): static
    {
        return $this->state(fn (): array => [
            'sales_state' => SeasonTicketSalesState::ComingSoon,
            'sales_opens_at' => now()->addMonth(),
            'sales_closes_at' => now()->addMonths(2),
        ]);
    }

    public function available(): static
    {
        return $this->state(fn (): array => [
            'sales_state' => SeasonTicketSalesState::Available,
            'sales_opens_at' => now()->subMonth(),
            'sales_closes_at' => now()->addMonth(),
        ]);
    }

    public function soldOut(): static
    {
        return $this->state(fn (): array => [
            'sales_state' => SeasonTicketSalesState::SoldOut,
            'sales_opens_at' => now()->subMonths(2),
            'sales_closes_at' => now()->addMonth(),
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (): array => [
            'sales_state' => SeasonTicketSalesState::Closed,
            'sales_opens_at' => now()->subMonths(2),
            'sales_closes_at' => now()->subMonth(),
            'registration_url' => null,
        ]);
    }

    public function withEvents(int $count = 3): static
    {
        return $this->afterCreating(function (SeasonTicket $seasonTicket) use ($count): void {
            Event::factory()
                ->count($count)
                ->for($seasonTicket->season)
                ->create();
        });
    }
}
