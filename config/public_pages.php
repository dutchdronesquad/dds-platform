<?php

/**
 * Temporary public shell page content.
 *
 * This keeps placeholder copy out of route definitions until these pages move
 * to first-class models or constrained managed content.
 */
return [
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
