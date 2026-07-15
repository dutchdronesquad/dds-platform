<?php

namespace Database\Factories;

use App\Models\MediaAsset;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MediaAsset>
 */
class MediaAssetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'disk' => 'public',
            'path' => 'uploads/'.now()->format('Y/m').'/'.Str::uuid().'.jpg',
            'original_filename' => fake()->unique()->slug(3).'.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => fake()->numberBetween(50_000, 5_000_000),
            'width' => fake()->numberBetween(1_200, 3_840),
            'height' => fake()->numberBetween(800, 2_160),
            'alt_text' => ['en' => fake()->sentence(8)],
            'is_decorative' => false,
        ];
    }

    public function decorative(): static
    {
        return $this->state(fn (): array => [
            'alt_text' => null,
            'is_decorative' => true,
        ]);
    }

    public function pdf(): static
    {
        return $this->state(fn (): array => [
            'path' => 'uploads/'.now()->format('Y/m').'/'.Str::uuid().'.pdf',
            'original_filename' => fake()->unique()->slug(3).'.pdf',
            'mime_type' => 'application/pdf',
            'width' => null,
            'height' => null,
        ]);
    }

    public function withDutchTranslation(): static
    {
        return $this->state(function (array $attributes): array {
            /** @var array<string, string> $altText */
            $altText = $attributes['alt_text'];

            return [
                'alt_text' => [...$altText, 'nl' => fake()->sentence(8)],
            ];
        });
    }
}
