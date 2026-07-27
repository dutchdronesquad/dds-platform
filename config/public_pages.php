<?php

/**
 * Temporary public shell page content.
 *
 * This keeps placeholder copy out of route definitions until these pages move
 * to first-class models or constrained managed content.
 */
return [
    'house_rules' => [
        'title' => 'House Rules',
        'eyebrow' => 'Regels',
        'description' => 'De huisregels geven straks duidelijke verwachtingen voor deelname, veiligheid en gedrag binnen de DDS-community.',
        'visual' => [
            'src' => '/images/dds/racing/pilot-preparing-drone.jpg',
            'alt' => 'FPV-piloot bereidt veilig een racedrone voor',
            'position' => '44% center',
        ],
        'primaryAction' => [
            'label' => 'Bekijk locaties',
            'href' => '/locations',
        ],
        'sections' => [
            [
                'heading' => 'Veilig deelnemen',
                'body' => 'Deze pagina krijgt ruimte voor basisregels rond vliegen, privacy, materiaal en omgang met elkaar.',
            ],
            [
                'heading' => 'Praktische toepassing',
                'body' => 'Huisregels kunnen later gekoppeld worden aan events, locaties en onboardingmateriaal.',
            ],
        ],
    ],
    'contact' => [
        'title' => 'Contact',
        'eyebrow' => 'Bereik DDS',
        'description' => 'Wil je meer weten over Dutch Drone Squad of over de vliegavonden die we organiseren? Neem dan gerust contact op.',
        'visual' => [
            'src' => '/images/dds/racing/trainingsavond-overzicht.jpg',
            'alt' => 'Overzicht van het indoor FPV-parcours in het Sportpaleis Alkmaar tijdens een trainingsavond',
            'position' => 'center 42%',
        ],
        'actions' => [
            [
                'label' => 'Bekijk events',
                'href' => '/events',
            ],
        ],
        'sections' => [
            [
                'heading' => 'Waarvoor contact',
                'body' => 'Gebruik het formulier voor deelname, samenwerking, persvragen of projectvoorstellen.',
            ],
            [
                'heading' => 'Zorgvuldige opvolging',
                'body' => 'Iedere aanvraag wordt opgeslagen en blijft beschikbaar voor onze beheerders, ook bij een tijdelijk mailprobleem.',
            ],
        ],
    ],
];
