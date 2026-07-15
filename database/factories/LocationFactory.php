<?php

namespace Database\Factories;

use App\Enums\LocationEnvironment;
use App\Models\Location;
use App\Models\MediaAsset;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'cover_image_id' => null,
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'description' => ['en' => fake()->paragraph()],
            'street' => fake()->streetName(),
            'house_number' => fake()->buildingNumber(),
            'postal_code' => fake()->postcode(),
            'city' => fake()->city(),
            'country_code' => 'NL',
            'environment' => LocationEnvironment::Indoor,
            'floor_size_square_metres' => fake()->numberBetween(250, 3_000),
            'ceiling_height_metres' => fake()->randomFloat(2, 3, 15),
            'facilities' => fake()->randomElements([
                'parking',
                'power',
                'toilets',
                'tables_and_chairs',
                'catering',
                'wifi',
            ], 3),
            'website_url' => fake()->url(),
            'latitude' => fake()->latitude(50.75, 53.55),
            'longitude' => fake()->longitude(3.35, 7.25),
        ];
    }

    public function withCoverImage(): static
    {
        return $this->state(fn (): array => [
            'cover_image_id' => MediaAsset::factory(),
        ]);
    }

    public function withDutchTranslation(): static
    {
        return $this->state(function (array $attributes): array {
            /** @var array<string, string> $description */
            $description = $attributes['description'];

            return [
                'description' => [...$description, 'nl' => fake()->paragraph()],
            ];
        });
    }
}
