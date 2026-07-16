<?php

namespace Database\Factories;

use App\Models\Season;
use App\Models\SeasonTicket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Season>
 */
class SeasonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Seizoen '.fake()->unique()->numberBetween(2026, 2100),
        ];
    }

    public function withTicketOffer(): static
    {
        return $this->has(SeasonTicket::factory(), 'seasonTicket');
    }
}
