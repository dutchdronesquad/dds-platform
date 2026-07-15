<?php

namespace Database\Factories;

use App\Enums\ArticleCategory;
use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\MediaAsset;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(6);

        return [
            'author_id' => User::factory(),
            'cover_image_id' => null,
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numerify('###'),
            'content' => fake()->paragraphs(5, true),
            'published_at' => null,
            'status' => ArticleStatus::Draft,
            'category' => ArticleCategory::News,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => ArticleStatus::Published,
            'published_at' => now(),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (): array => [
            'status' => ArticleStatus::Archived,
        ]);
    }

    public function raceReport(): static
    {
        return $this->state(fn (): array => [
            'category' => ArticleCategory::RaceReport,
        ]);
    }

    public function withoutAuthor(): static
    {
        return $this->state(fn (): array => [
            'author_id' => null,
        ]);
    }

    public function withCoverImage(): static
    {
        return $this->state(fn (): array => [
            'cover_image_id' => MediaAsset::factory(),
        ]);
    }
}
