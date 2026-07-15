<?php

namespace Database\Factories;

use App\Models\Season;
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
            'price_cents' => fake()->optional()->numberBetween(5_000, 15_000),
            'ticket_capacity' => fake()->optional()->numberBetween(5, 25),
        ];
    }
}
