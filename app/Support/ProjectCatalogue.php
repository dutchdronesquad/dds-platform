<?php

namespace App\Support;

use InvalidArgumentException;

final readonly class ProjectCatalogue
{
    /**
     * @param  list<ProjectCatalogueEntry>  $entries
     */
    private function __construct(private array $entries) {}

    public static function fromConfig(): self
    {
        $entries = config('project_catalogue.projects');

        if (! is_array($entries)) {
            throw new InvalidArgumentException('The project catalogue configuration must contain a projects list.');
        }

        return self::fromArray($entries);
    }

    /**
     * @param  array<mixed>  $entries
     */
    public static function fromArray(array $entries): self
    {
        if ($entries === [] || ! array_is_list($entries)) {
            throw new InvalidArgumentException('The project catalogue must be a non-empty list.');
        }

        $catalogueEntries = [];
        $knownSlugs = [];

        foreach ($entries as $attributes) {
            if (! is_array($attributes)) {
                throw new InvalidArgumentException('Each project catalogue entry must be an array.');
            }

            $entry = ProjectCatalogueEntry::fromArray($attributes);

            if (isset($knownSlugs[$entry->slug])) {
                throw new InvalidArgumentException("Duplicate project slug [{$entry->slug}].");
            }

            $knownSlugs[$entry->slug] = true;
            $catalogueEntries[] = $entry;
        }

        return new self($catalogueEntries);
    }

    /**
     * @return list<ProjectCatalogueEntry>
     */
    public function all(): array
    {
        return $this->entries;
    }

    public function find(string $slug): ?ProjectCatalogueEntry
    {
        foreach ($this->entries as $entry) {
            if ($entry->slug === $slug) {
                return $entry;
            }
        }

        return null;
    }
}
