<?php

use App\Enums\LocationEnvironment;

return [
    'cleanup' => [
        'normalizer_version' => 2,
        'unavailable_links' => [
            'https://dutchdronesquad.nl/wp-content/uploads/2019/03/DDS-Informatie-en-voorschriften-V1.0-NL.pdf' => 'niet meer online beschikbaar',
            'https://dutchdronesquad.nl/wp-content/uploads/2019/03/DDS-Information-and-regulations-V1.0-ENG.pdf' => 'niet meer online beschikbaar',
            'https://dutchdronesquad.nl/livestream/' => 'niet meer online beschikbaar',
            'https://dutchdronesquad.nl/wp-content/uploads/2019/11/DDS-Informatie-en-voorschriften-V1.4-The-next-race.pdf' => 'niet meer online beschikbaar',
            'https://dutchdronesquad.nl/wp-content/uploads/2019/11/DDS-Information-and-regulations-V1.4-The-next-race.pdf' => 'niet meer online beschikbaar',
        ],
    ],
    'locations' => [
        'sportpaleis-alkmaar' => [
            'name' => 'Sportpaleis Alkmaar',
            'description' => [
                'nl' => 'Ruime indoor wielerbaan waar DDS een wisselend FPV-parcours opbouwt en rondetijden meet met racetimers.',
            ],
            'street' => 'Terborchlaan',
            'house_number' => '200',
            'postal_code' => '1816 LE',
            'city' => 'Alkmaar',
            'country_code' => 'NL',
            'environment' => LocationEnvironment::Indoor->value,
            'floor_size_square_metres' => 2000,
            'ceiling_height_metres' => 11.0,
            'facilities' => ['parking', 'power', 'toilets', 'tables_and_chairs'],
            'website_url' => 'https://sportpaleis-alkmaar.nl/',
            'latitude' => null,
            'longitude' => null,
        ],
        'sporthal-koggenhal' => [
            'name' => 'Sporthal Koggenhal',
            'description' => [
                'nl' => 'Sporthal met vaste blacklightinstallatie waar DDS indoor FPV-trainingen organiseert.',
            ],
            'street' => 'Dwingel',
            'house_number' => '4',
            'postal_code' => '1648 JM',
            'city' => 'De Goorn',
            'country_code' => 'NL',
            'environment' => LocationEnvironment::Indoor->value,
            'floor_size_square_metres' => 1350,
            'ceiling_height_metres' => 9.0,
            'facilities' => ['parking', 'power', 'toilets'],
            'website_url' => null,
            'latitude' => null,
            'longitude' => null,
        ],
        'sporthal-oosterhout' => [
            'name' => 'Sporthal Oosterhout',
            'description' => [
                'nl' => 'Alkmaarse uitwijklocatie waar DDS bij gebruik een indoor FPV-parcours opbouwt.',
            ],
            'street' => 'Vondelstraat',
            'house_number' => '35',
            'postal_code' => '1813 AA',
            'city' => 'Alkmaar',
            'country_code' => 'NL',
            'environment' => LocationEnvironment::Indoor->value,
            'floor_size_square_metres' => 1000,
            'ceiling_height_metres' => 9.0,
            'facilities' => ['parking', 'power', 'toilets'],
            'website_url' => null,
            'latitude' => null,
            'longitude' => null,
        ],
    ],
];
