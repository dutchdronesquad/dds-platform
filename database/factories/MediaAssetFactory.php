<?php

namespace Database\Factories;

use App\Models\MediaAsset;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @extends Factory<MediaAsset>
 */
class MediaAssetFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'alt_text' => ['en' => fake()->sentence(8)],
            'archived_at' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (MediaAsset $mediaAsset): void {
            if ($mediaAsset->file() instanceof Media) {
                return;
            }

            $filename = fake()->unique()->slug(3).'.jpg';

            $mediaAsset->media()->create($this->mediaAttributes(
                filename: $filename,
                mimeType: 'image/jpeg',
                width: fake()->numberBetween(1_200, 3_840),
                height: fake()->numberBetween(800, 2_160),
            ));
            $mediaAsset->unsetRelation('media');
        });
    }

    public function named(string $filename): static
    {
        return $this->afterCreating(function (MediaAsset $mediaAsset) use ($filename): void {
            $mediaAsset->file()?->update([
                'name' => $filename,
                'file_name' => $filename,
            ]);
        });
    }

    public function pdf(?string $filename = null): static
    {
        return $this->state(fn (): array => ['alt_text' => null])
            ->afterCreating(function (MediaAsset $mediaAsset) use ($filename): void {
                $pdfFilename = $filename ?? fake()->unique()->slug(3).'.pdf';

                $mediaAsset->file()?->update([
                    'name' => $pdfFilename,
                    'file_name' => $pdfFilename,
                    'mime_type' => 'application/pdf',
                    'custom_properties' => [
                        'width' => null,
                        'height' => null,
                    ],
                ]);
            });
    }

    public function archived(): static
    {
        return $this->state(fn (): array => [
            'archived_at' => now(),
        ]);
    }

    /** @return array<string, mixed> */
    private function mediaAttributes(
        string $filename,
        string $mimeType,
        ?int $width,
        ?int $height,
    ): array {
        $disk = config('media-library.disk_name', 'public');

        return [
            'uuid' => (string) Str::uuid(),
            'collection_name' => MediaAsset::COLLECTION,
            'name' => $filename,
            'file_name' => $filename,
            'mime_type' => $mimeType,
            'disk' => is_string($disk) ? $disk : 'public',
            'conversions_disk' => null,
            'size' => fake()->numberBetween(50_000, 5_000_000),
            'manipulations' => [],
            'custom_properties' => [
                'width' => $width,
                'height' => $height,
            ],
            'generated_conversions' => [],
            'responsive_images' => [],
            'order_column' => 1,
        ];
    }
}
