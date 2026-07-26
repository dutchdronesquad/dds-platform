<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Powers a search-as-you-type address field: `suggest()` lists candidate
 * addresses for a partial query, and `lookup()` resolves a chosen PDOK
 * suggestion to its full structured address and coordinates.
 *
 * Dutch addresses are looked up through the free PDOK Locatieserver, which
 * is backed by the BAG (the Kadaster's authoritative address register) and
 * gives exact, official matches. When PDOK has no match — most likely
 * because the address isn't in the Netherlands — suggestions fall back to
 * Photon (komoot's free, keyless, worldwide geocoder, which unlike
 * Nominatim explicitly allows search-as-you-type use). Photon already
 * returns the full structured address per suggestion, so those don't need
 * a separate lookup step.
 */
final class AddressGeocoder
{
    private const string PDOK_SUGGEST_ENDPOINT = 'https://api.pdok.nl/bzk/locatieserver/search/v3_1/suggest';

    private const string PDOK_LOOKUP_ENDPOINT = 'https://api.pdok.nl/bzk/locatieserver/search/v3_1/lookup';

    private const string PHOTON_ENDPOINT = 'https://photon.komoot.io/api';

    /** @return list<array{id: string, label: string, source: string, resolved: array{street: string, houseNumber: string, postalCode: string, city: string, countryCode: string, latitude: string, longitude: string}|null}> */
    public function suggest(string $query): array
    {
        $suggestions = $this->suggestPdok($query);

        return $suggestions !== [] ? $suggestions : $this->suggestPhoton($query);
    }

    /**
     * @return array{street: string, houseNumber: string, postalCode: string, city: string, countryCode: string, latitude: string, longitude: string}|null
     */
    public function lookup(string $source, string $id): ?array
    {
        return $source === 'pdok' ? $this->lookupPdok($id) : null;
    }

    /** @return list<array{id: string, label: string, source: string, resolved: null}> */
    private function suggestPdok(string $query): array
    {
        try {
            $response = Http::timeout(5)->get(self::PDOK_SUGGEST_ENDPOINT, [
                'q' => $query,
                'fq' => 'type:adres',
                'rows' => 10,
            ]);
        } catch (Throwable $exception) {
            Log::warning('PDOK address suggestion request failed.', [
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

            $suggestions[] = [
                'id' => $doc['id'],
                'label' => $doc['weergavenaam'],
                'source' => 'pdok',
                'resolved' => null,
            ];
        }

        return $suggestions;
    }

    /**
     * @return array{street: string, houseNumber: string, postalCode: string, city: string, countryCode: string, latitude: string, longitude: string}|null
     */
    private function lookupPdok(string $id): ?array
    {
        try {
            $response = Http::timeout(5)->get(self::PDOK_LOOKUP_ENDPOINT, ['id' => $id]);
        } catch (Throwable $exception) {
            Log::warning('PDOK address lookup request failed.', [
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
            'countryCode' => 'NL',
            'latitude' => number_format((float) $coordinates[2], 7, '.', ''),
            'longitude' => number_format((float) $coordinates[1], 7, '.', ''),
        ];
    }

    /** @return list<array{id: string, label: string, source: string, resolved: array{street: string, houseNumber: string, postalCode: string, city: string, countryCode: string, latitude: string, longitude: string}}> */
    private function suggestPhoton(string $query): array
    {
        try {
            $response = Http::timeout(5)->get(self::PHOTON_ENDPOINT, [
                'q' => $query,
                'limit' => 10,
                'lang' => 'en',
            ]);
        } catch (Throwable $exception) {
            Log::warning('Photon address suggestion request failed.', [
                'query' => $query,
                'exception' => $exception->getMessage(),
            ]);

            return [];
        }

        if (! $response->successful()) {
            return [];
        }

        $features = $response->json('features');

        if (! is_array($features)) {
            return [];
        }

        $suggestions = [];

        foreach ($features as $index => $feature) {
            $resolved = $this->resolvePhotonFeature($feature);

            if ($resolved === null) {
                continue;
            }

            $houseNumberSuffix = $resolved['houseNumber'] !== '' ? " {$resolved['houseNumber']}" : '';

            $suggestions[] = [
                'id' => "photon-{$index}",
                'label' => "{$resolved['street']}{$houseNumberSuffix}, {$resolved['postalCode']} {$resolved['city']}, {$resolved['countryCode']}",
                'source' => 'photon',
                'resolved' => $resolved,
            ];
        }

        return $suggestions;
    }

    /**
     * @return array{street: string, houseNumber: string, postalCode: string, city: string, countryCode: string, latitude: string, longitude: string}|null
     */
    private function resolvePhotonFeature(mixed $feature): ?array
    {
        if (! is_array($feature)) {
            return null;
        }

        $properties = $feature['properties'] ?? null;
        $coordinates = $feature['geometry']['coordinates'] ?? null;

        if (
            ! is_array($properties)
            || ! is_array($coordinates)
            || count($coordinates) !== 2
            || ! is_string($properties['street'] ?? null)
            || ! is_string($properties['city'] ?? null)
            || ! is_string($properties['countrycode'] ?? null)
        ) {
            return null;
        }

        return [
            'street' => $properties['street'],
            'houseNumber' => is_string($properties['housenumber'] ?? null) ? $properties['housenumber'] : '',
            'postalCode' => is_string($properties['postcode'] ?? null) ? $properties['postcode'] : '',
            'city' => $properties['city'],
            'countryCode' => strtoupper($properties['countrycode']),
            'latitude' => number_format((float) $coordinates[1], 7, '.', ''),
            'longitude' => number_format((float) $coordinates[0], 7, '.', ''),
        ];
    }
}
