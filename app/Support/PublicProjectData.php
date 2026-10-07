<?php

namespace App\Support;

/**
 * @phpstan-type PublicProjectMedium array{src: string, darkSrc?: string, alt: string}
 * @phpstan-type PublicProjectSummary array{
 *     slug: string,
 *     title: string,
 *     summary: string,
 *     type: array{value: string, label: string},
 *     primaryLink: array{label: string, url: string}|null,
 *     supportingLinks: list<array{label: string, url: string}>,
 *     credits: list<string>,
 *     audience: string,
 *     media: list<PublicProjectMedium>,
 *     featured: bool,
 *     videoUrl: ?string,
 *     subprojects: list<array{title: string, summary: string, primaryLink: array{label: string, url: string}, supportingLinks: list<array{label: string, url: string}>, media: list<PublicProjectMedium>}>,
 * }
 */
final class PublicProjectData
{
    /**
     * @return list<PublicProjectSummary>
     */
    public function forOverview(): array
    {
        return array_values(collect($this->all())
            ->shuffle()
            ->all());
    }

    /**
     * @return list<PublicProjectSummary>
     */
    public function all(): array
    {
        return array_map(
            fn (ProjectCatalogueEntry $entry): array => $this->summary($entry),
            ProjectCatalogue::fromConfig()->all(),
        );
    }

    /**
     * @return PublicProjectSummary
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
                static fn (array $medium): array => self::medium($medium),
                $entry->media,
            ),
            'featured' => $entry->featured,
            'videoUrl' => $entry->videoUrl,
            'subprojects' => array_map(static fn (array $subproject): array => [
                ...$subproject,
                'media' => array_map(static fn (array $medium): array => self::medium($medium), $subproject['media']),
            ], $entry->subprojects),
        ];
    }

    /**
     * @param  array{path: string, dark_path?: string, alt: string}  $medium
     * @return PublicProjectMedium
     */
    private static function medium(array $medium): array
    {
        if (isset($medium['dark_path'])) {
            return [
                'src' => $medium['path'],
                'darkSrc' => $medium['dark_path'],
                'alt' => $medium['alt'],
            ];
        }

        return [
            'src' => $medium['path'],
            'alt' => $medium['alt'],
        ];
    }
}
