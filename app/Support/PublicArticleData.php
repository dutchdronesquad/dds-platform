<?php

namespace App\Support;

use App\Models\Article;
use App\Models\MediaAsset;
use Illuminate\Support\Arr;

final class PublicArticleData
{
    public function __construct(private MarkdownRenderer $markdown) {}

    /**
     * @return array{
     *     id: int,
     *     slug: string,
     *     title: string,
     *     excerpt: string|null,
     *     category: string,
     *     publishedAt: string|null,
     *     author: array{name: string}|null,
     *     image: array{src: string, alt: string},
     * }
     */
    public function summary(Article $article): array
    {
        return [
            'id' => $article->id,
            'slug' => $article->slug,
            'title' => $article->title,
            'excerpt' => str($this->markdown->toPlainText($article->content))
                ->limit(150)
                ->toString(),
            'category' => $article->category->value,
            'publishedAt' => $article->published_at?->toIso8601String(),
            'author' => $article->author === null ? null : [
                'name' => $article->author->name,
            ],
            'image' => $this->image($article),
        ];
    }

    /** @return array{src: string, alt: string} */
    public function image(Article $article): array
    {
        if (! $article->coverImage instanceof MediaAsset) {
            return [
                'src' => '/images/dds/racing/pilot-at-training.jpg',
                'alt' => 'Piloot tijdens een indoor training van Dutch Drone Squad',
            ];
        }

        $altText = $article->coverImage->alt_text;

        return [
            'src' => $article->coverImage->url(),
            'alt' => Arr::get($altText, app()->getLocale())
                ?? Arr::get($altText, 'en')
                ?? $article->title,
        ];
    }
}
