<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Looks up Dutch addresses through the free PDOK Locatieserver, which is
 * backed by the BAG (the Kadaster's authoritative address register). Used
 * to power a search-as-you-type address field: `suggest()` lists candidate
 * addresses for a partial query, and `lookup()` resolves a chosen suggestion
 * to its full structured address and coordinates.
 */
final class AddressGeocoder
{
    private const string SUGGEST_ENDPOINT = 'https://api.pdok.nl/bzk/locatieserver/search/v3_1/suggest';

    private const string LOOKUP_ENDPOINT = 'https://api.pdok.nl/bzk/locatieserver/search/v3_1/lookup';

    /** @return list<array{id: string, label: string}> */
    public function suggest(string $query): array
    {
        try {
            $response = Http::timeout(5)->get(self::SUGGEST_ENDPOINT, [
                'q' => $query,
                'fq' => 'type:adres',
                'rows' => 10,
            ]);
        } catch (Throwable $exception) {
            Log::warning('Address suggestion request failed.', [
                'query' => $query,
                'exception' => $exception->getMessage(),
            ]);

            return [];
        }

        if (! $response->successful()) {
            return [];
        }

        $docs = $response->json('response.docs');

        if (! is_array($docs)) {
            return [];
        }

        $suggestions = [];

        foreach ($docs as $doc) {
            if (! is_array($doc) || ! isset($doc['id'], $doc['weergavenaam']) || ! is_string($doc['id']) || ! is_string($doc['weergavenaam'])) {
                continue;
            }

            $suggestions[] = ['id' => $doc['id'], 'label' => $doc['weergavenaam']];
        }

        return $suggestions;
    }

    /**
     * @return array{street: string, houseNumber: string, postalCode: string, city: string, latitude: string, longitude: string}|null
     */
    public function lookup(string $id): ?array
    {
        try {
            $response = Http::timeout(5)->get(self::LOOKUP_ENDPOINT, ['id' => $id]);
        } catch (Throwable $exception) {
            Log::warning('Address lookup request failed.', [
                'id' => $id,
                'exception' => $exception->getMessage(),
            ]);

            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $match = $response->json('response.docs.0');

        if (
            ! is_array($match)
            || ! isset($match['straatnaam'], $match['huisnummer'], $match['postcode'], $match['woonplaatsnaam'], $match['centroide_ll'])
            || ! is_string($match['centroide_ll'])
            || ! preg_match('/POINT\(([-\d.]+) ([-\d.]+)\)/', $match['centroide_ll'], $coordinates)
        ) {
            return null;
        }

        return [
            'street' => (string) $match['straatnaam'],
            'houseNumber' => (string) $match['huisnummer'],
            'postalCode' => (string) $match['postcode'],
            'city' => (string) $match['woonplaatsnaam'],
            'latitude' => number_format((float) $coordinates[2], 7, '.', ''),
            'longitude' => number_format((float) $coordinates[1], 7, '.', ''),
        ];
    }
}
