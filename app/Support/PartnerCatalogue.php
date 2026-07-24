<?php

namespace App\Support;

use InvalidArgumentException;

final readonly class PartnerCatalogue
{
    /**
     * @param  list<PartnerCatalogueEntry>  $entries
     */
    private function __construct(private array $entries) {}

    public static function fromConfig(): self
    {
        $entries = config('partner_catalogue.partners');

        if (! is_array($entries)) {
            throw new InvalidArgumentException('The partner catalogue configuration must contain a partners list.');
        }

        return self::fromArray($entries);
    }

    /**
     * @param  array<mixed>  $entries
     */
    public static function fromArray(array $entries): self
    {
        if (! array_is_list($entries)) {
            throw new InvalidArgumentException('The partner catalogue must be a list.');
        }

        $catalogueEntries = [];
        $knownKeys = [];

        foreach ($entries as $attributes) {
            if (! is_array($attributes)) {
                throw new InvalidArgumentException('Each partner catalogue entry must be an array.');
            }

            $entry = PartnerCatalogueEntry::fromArray($attributes);

            if (isset($knownKeys[$entry->key])) {
                throw new InvalidArgumentException("Duplicate partner key [{$entry->key}].");
            }

            if (! is_file(public_path(ltrim($entry->logoPath, '/')))) {
                throw new InvalidArgumentException("Partner [{$entry->key}] logo asset [{$entry->logoPath}] does not exist.");
            }

            $knownKeys[$entry->key] = true;
            $catalogueEntries[] = $entry;
        }

        usort(
            $catalogueEntries,
            static fn (PartnerCatalogueEntry $left, PartnerCatalogueEntry $right): int => [
                $left->sortOrder,
                $left->key,
            ] <=> [
                $right->sortOrder,
                $right->key,
            ],
        );

        return new self($catalogueEntries);
    }

    /**
     * @return list<PartnerCatalogueEntry>
     */
    public function all(): array
    {
        return $this->entries;
    }

    /**
     * @return list<PartnerCatalogueEntry>
     */
    public function forHomepage(): array
    {
        return array_values(array_filter(
            $this->entries,
            static fn (PartnerCatalogueEntry $entry): bool => $entry->showOnHomepage,
        ));
    }

    public function find(string $key): ?PartnerCatalogueEntry
    {
        foreach ($this->entries as $entry) {
            if ($entry->key === $key) {
                return $entry;
            }
        }

        return null;
    }
}
