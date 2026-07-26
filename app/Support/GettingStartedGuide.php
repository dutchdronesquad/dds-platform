<?php

namespace App\Support;

final readonly class GettingStartedGuide
{
    /**
     * @param  array{src: string, alt: string, position?: string}  $heroImage
     */
    private function __construct(
        public string $slug,
        public string $title,
        public string $eyebrow,
        public string $summary,
        public array $heroImage,
        public string $editorialOwner,
        public string $reviewedAt,
        public int $sortOrder,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function fromArray(array $attributes): self
    {
        /** @var array{src: string, alt: string, position?: string} $heroImage */
        $heroImage = $attributes['hero_image'];

        return new self(
            slug: $attributes['slug'],
            title: $attributes['title'],
            eyebrow: $attributes['eyebrow'],
            summary: $attributes['summary'],
            heroImage: $heroImage,
            editorialOwner: $attributes['editorial_owner'],
            reviewedAt: $attributes['reviewed_at'],
            sortOrder: $attributes['sort_order'],
        );
    }
}
