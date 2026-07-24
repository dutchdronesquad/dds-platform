<?php

namespace App\Support;

/**
 * @phpstan-type PublicPartner array{
 *     key: string,
 *     name: string,
 *     websiteUrl: string,
 *     logoSrc: string,
 *     logoAlt: string,
 *     description: ?string,
 * }
 */
final class PublicPartnerData
{
    /**
     * @return list<PublicPartner>
     */
    public function all(): array
    {
        return array_map(
            static fn (PartnerCatalogueEntry $entry): array => self::partner($entry),
            PartnerCatalogue::fromConfig()->all(),
        );
    }

    /**
     * @return list<PublicPartner>
     */
    public function forHomepage(): array
    {
        return array_map(
            static fn (PartnerCatalogueEntry $entry): array => self::partner($entry),
            PartnerCatalogue::fromConfig()->forHomepage(),
        );
    }

    /**
     * @return PublicPartner
     */
    private static function partner(PartnerCatalogueEntry $entry): array
    {
        return [
            'key' => $entry->key,
            'name' => $entry->name,
            'websiteUrl' => $entry->websiteUrl,
            'logoSrc' => $entry->logoPath,
            'logoAlt' => $entry->logoAlt,
            'description' => $entry->description,
        ];
    }
}
