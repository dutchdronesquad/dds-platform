<?php

namespace App\Support;

use Illuminate\Support\Str;

final class SeoMetadata
{
    /**
     * @param  array{
     *     title?: string,
     *     description?: string,
     *     canonical_path?: string,
     *     robots?: string,
     *     image_path?: string,
     *     image_alt?: string,
     *     open_graph_type?: string,
     *     site_name?: string,
     * }  $overrides
     * @return array{
     *     title: string,
     *     description: string,
     *     canonicalUrl: string,
     *     robots: string,
     *     openGraph: array{
     *         title: string,
     *         description: string,
     *         type: string,
     *         url: string,
     *         image: string,
     *         imageAlt: string,
     *         siteName: string,
     *     },
     * }
     */
    public function forPage(string $page, array $overrides = []): array
    {
        /** @var array<string, string> $defaults */
        $defaults = config('seo.defaults', []);

        /** @var array<string, string> $pageMetadata */
        $pageMetadata = config("seo.pages.{$page}", []);

        $metadata = array_replace($defaults, $pageMetadata, $overrides);
        $title = $metadata['title'];
        $description = $metadata['description'];
        $canonicalUrl = $this->absoluteUrl($metadata['canonical_path']);
        $siteName = $metadata['site_name'];

        return [
            'title' => $title,
            'description' => $description,
            'canonicalUrl' => $canonicalUrl,
            'robots' => $metadata['robots'],
            'openGraph' => [
                'title' => $title === $siteName ? $siteName : "{$title} - {$siteName}",
                'description' => $description,
                'type' => $metadata['open_graph_type'],
                'url' => $canonicalUrl,
                'image' => $this->absoluteUrl($metadata['image_path']),
                'imageAlt' => $metadata['image_alt'],
                'siteName' => $siteName,
            ],
        ];
    }

    private function absoluteUrl(string $path): string
    {
        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return Str::of((string) config('app.url'))
            ->rtrim('/')
            ->append('/', Str::of($path)->ltrim('/'))
            ->toString();
    }
}
