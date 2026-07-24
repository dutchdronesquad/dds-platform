<?php

namespace App\Support;

final class PublicProjectData
{
    /**
     * @return list<array{
     *     slug: string,
     *     title: string,
     *     summary: string,
     *     type: array{value: string, label: string},
     *     primaryLink: array{label: string, url: string},
     *     supportingLinks: list<array{label: string, url: string}>,
     *     credits: list<string>,
     *     audience: string,
     *     media: list<array{src: string, darkSrc?: string, alt: string}>,
     *     featured: bool,
     *     videoUrl: ?string,
     * }>
     */
    public function forOverview(): array
    {
        return collect($this->all())
            ->shuffle()
            ->values()
            ->all();
    }

    /**
     * @return list<array{
     *     slug: string,
     *     title: string,
     *     summary: string,
     *     type: array{value: string, label: string},
     *     primaryLink: array{label: string, url: string},
     *     supportingLinks: list<array{label: string, url: string}>,
     *     credits: list<string>,
     *     audience: string,
     *     media: list<array{src: string, darkSrc?: string, alt: string}>,
     *     featured: bool,
     *     videoUrl: ?string,
     * }>
     */
    public function all(): array
    {
        return array_map(
            fn (ProjectCatalogueEntry $entry): array => $this->summary($entry),
            ProjectCatalogue::fromConfig()->all(),
        );
    }

    /**
     * @return array{
     *     slug: string,
     *     title: string,
     *     summary: string,
     *     type: array{value: string, label: string},
     *     primaryLink: array{label: string, url: string},
     *     supportingLinks: list<array{label: string, url: string}>,
     *     credits: list<string>,
     *     audience: string,
     *     media: list<array{src: string, darkSrc?: string, alt: string}>,
     *     featured: bool,
     * }
     */
    private function summary(ProjectCatalogueEntry $entry): array
    {
        return [
            'slug' => $entry->slug,
            'title' => $entry->title,
            'summary' => $entry->summary,
            'type' => [
                'value' => $entry->type->value,
                'label' => $entry->type->label(),
            ],
            'primaryLink' => $entry->primaryLink,
            'supportingLinks' => $entry->supportingLinks,
            'credits' => $entry->credits,
            'audience' => $entry->audience,
            'media' => array_map(
                static fn (array $medium): array => [
                    'src' => $medium['path'],
                    ...(isset($medium['dark_path']) ? ['darkSrc' => $medium['dark_path']] : []),
                    'alt' => $medium['alt'],
                ],
                $entry->media,
            ),
            'featured' => $entry->featured,
            'videoUrl' => $entry->videoUrl,
        ];
    }
}
