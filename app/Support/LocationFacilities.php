<?php

namespace App\Support;

use Illuminate\Validation\Rule;

class LocationFacilities
{
    /** @return array<string, array{label: string, group: string, options: array<string, string>}> */
    public static function catalogue(): array
    {
        return [
            'parking' => ['label' => 'Parkeren', 'group' => 'Bereikbaarheid', 'options' => ['none' => 'Niet aanwezig', 'available' => 'Parkeren (type onbekend)', 'free' => 'Gratis parkeren', 'paid' => 'Betaald parkeren']],
            'catering' => ['label' => 'Catering', 'group' => 'Comfort', 'options' => ['none' => 'Niet aanwezig', 'available' => 'Catering (type onbekend)', 'on_site' => 'Catering op locatie', 'vending' => 'Eten/drinken uit automaat', 'nearby' => 'Catering in de buurt']],
            'wifi' => ['label' => 'Wifi', 'group' => 'Vliegen en laden', 'options' => ['none' => 'Niet aanwezig', 'available' => 'Wifi (toegang onbekend)', 'public' => 'Publieke wifi', 'staff_only' => 'Wifi alleen voor medewerkers']],
            'power' => ['label' => 'Stroomvoorziening', 'group' => 'Vliegen en laden', 'options' => []],
            'charging' => ['label' => 'Oplaadmogelijkheid', 'group' => 'Vliegen en laden', 'options' => []],
            'tables_and_chairs' => ['label' => 'Tafels en stoelen', 'group' => 'Vliegen en laden', 'options' => []],
            'toilets' => ['label' => 'Toiletten', 'group' => 'Comfort', 'options' => []],
            'heating' => ['label' => 'Verwarming', 'group' => 'Comfort', 'options' => []],
            'ventilation' => ['label' => 'Ventilatie', 'group' => 'Comfort', 'options' => []],
            'spectator_seating' => ['label' => 'Tribune / zitplaatsen voor publiek', 'group' => 'Publiek en veiligheid', 'options' => []],
            'spectator_area' => ['label' => 'Publieksruimte', 'group' => 'Publiek en veiligheid', 'options' => []],
            'wheelchair_accessible' => ['label' => 'Rolstoeltoegankelijk', 'group' => 'Bereikbaarheid', 'options' => []],
            'first_aid_aed' => ['label' => 'EHBO / AED beschikbaar', 'group' => 'Publiek en veiligheid', 'options' => []],
        ];
    }

    /** @return array<string, mixed> */
    public static function rules(): array
    {
        $rules = ['facilities' => ['nullable', 'array:'.implode(',', [...array_keys(self::catalogue()), 'legacy'])], 'facilities.legacy' => ['sometimes', 'array', 'list'], 'facilities.legacy.*' => ['string', 'max:100']];
        foreach (self::catalogue() as $key => $facility) {
            $rules['facilities.'.$key] = $facility['options'] === [] ? ['sometimes', 'boolean'] : ['sometimes', Rule::in(array_keys($facility['options']))];
        }

        return $rules;
    }

    /**
     * @param  array<mixed>|null  $values
     * @return array<string, mixed>
     */
    public static function normalize(?array $values): array
    {
        $values ??= [];
        if (array_is_list($values)) {
            $converted = [];
            $legacy = [];
            foreach ($values as $key) {
                if (is_string($key) && isset(self::catalogue()[$key])) {
                    $converted[$key] = self::catalogue()[$key]['options'] === [] ? true : 'available';
                } else {
                    $legacy[] = $key;
                }
            }
            if ($legacy !== []) {
                $converted['legacy'] = $legacy;
            }
            $values = $converted;
        }
        foreach (self::catalogue() as $key => $facility) {
            $values[$key] = $facility['options'] === [] ? (bool) ($values[$key] ?? false) : ($values[$key] ?? 'none');
        }

        return $values;
    }

    /** @param array<mixed>|null $values
     * @return list<string>
     */
    public static function labels(?array $values): array
    {
        $values = self::normalize($values);
        $labels = [];
        foreach (self::catalogue() as $key => $facility) {
            $value = $values[$key];
            if ($value !== false && $value !== 'none') {
                $label = $facility['options'] === [] ? $facility['label'] : (is_string($value) ? ($facility['options'][$value] ?? null) : null);
                if ($label !== null) {
                    $labels[] = $label;
                }
            }
        }

        foreach (is_array($values['legacy'] ?? null) ? $values['legacy'] : [] as $label) {
            if (is_string($label)) {
                $labels[] = $label;
            }
        }

        return $labels;
    }
}
