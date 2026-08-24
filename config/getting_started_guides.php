<?php

/**
 * Code-owned Getting Started guide catalogue.
 *
 * Phase 1 of the knowledge hub (docs/product/getting-started-knowledge-hub.md)
 * deliberately has no admin-editable `Guide` model yet. Each entry here maps
 * to one dedicated React page in resources/js/pages/public/getting-started/,
 * mirroring the code-owned partner catalogue pattern in
 * config/partner_catalogue.php.
 */
return [
    'guides' => [
        [
            'slug' => 'first-fpv-flight',
            'title' => 'Wat is FPV-vliegen?',
            'eyebrow' => 'Basis',
            'summary' => 'Ontdek hoe FPV-vliegen werkt, hoe je veilig begint en wanneer je klaar bent om bij DDS aan te sluiten.',
            'hero_image' => [
                'src' => '/images/dds/racing/drone-in-flight.jpg',
                'alt' => 'FPV-racedrone vliegt door het indoor parcours van Dutch Drone Squad',
                'position' => 'center center',
            ],
            'editorial_owner' => 'Dutch Drone Squad',
            'reviewed_at' => '2026-07-26',
            'sort_order' => 1,
        ],
        [
            'slug' => 'choosing-equipment',
            'title' => 'Uitrusting kiezen',
            'eyebrow' => 'Voorbereiding',
            'summary' => 'Wat een complete set bevat, welke onderdelen compatibel moeten zijn en wat je beter nog niet koopt.',
            'hero_image' => [
                'src' => '/images/dds/racing/controller-close-up.jpg',
                'alt' => 'Handen bedienen de zender van een FPV-racedrone',
                'position' => 'center center',
            ],
            'editorial_owner' => 'Dutch Drone Squad',
            'reviewed_at' => '2026-07-26',
            'sort_order' => 2,
        ],
        [
            'slug' => 'first-dds-event',
            'title' => 'Je eerste training bij DDS',
            'eyebrow' => 'De trainingsavond',
            'summary' => 'Van aanmelden en opbouwen tot de trackwalk, heats, veilig laden en samen opruimen: zo verloopt een trainingsavond.',
            'hero_image' => [
                'src' => '/images/dds/racing/pilots-with-goggles.jpg',
                'alt' => 'Drie FPV-piloten vliegen met videobril en zender tijdens een DDS-event',
                'position' => 'center center',
            ],
            'editorial_owner' => 'Dutch Drone Squad',
            'reviewed_at' => '2026-07-26',
            'sort_order' => 3,
        ],
    ],
];
