<?php

/**
 * Temporary homepage content.
 *
 * Event, article, project, and managed page records will replace these values
 * as their public domains become available. Keeping the bridge server-side
 * lets the Inertia page keep a stable prop contract during that transition.
 */
return [
    'upcomingEvents' => [
        [
            'availabilityLabel' => '12 van 15 plekken vrij',
            'dateLabel' => '13 SEP',
            'location' => 'Sportpaleis Alkmaar',
            'priceLabel' => '€15',
            'timeLabel' => '18:00–21:00',
            'title' => 'Indoor training · Round 01',
            'typeLabel' => '5-inch FPV racing',
        ],
        [
            'availabilityLabel' => '8 van 15 plekken vrij',
            'dateLabel' => '11 OKT',
            'location' => 'Sportpaleis Alkmaar',
            'priceLabel' => '€15',
            'timeLabel' => '18:00–21:00',
            'title' => 'Indoor training · Round 02',
            'typeLabel' => '5-inch FPV racing',
        ],
        [
            'availabilityLabel' => 'Aanmelding volgt',
            'dateLabel' => '15 NOV',
            'location' => 'Sportpaleis Alkmaar',
            'priceLabel' => '€15',
            'timeLabel' => '18:00–21:00',
            'title' => 'Indoor training · Round 03',
            'typeLabel' => '5-inch FPV racing',
        ],
    ],
    'upcomingEventsArePlaceholder' => true,
    'latestNews' => [
        [
            'dateLabel' => '9 september 2024',
            'excerpt' => 'De planning en veranderingen voor het nieuwe indoorseizoen.',
            'href' => 'https://dutchdronesquad.nl/seizoen-24-25/',
            'image' => [
                'alt' => 'FPV-piloot tijdens een indoor event van Dutch Drone Squad',
                'src' => '/images/dds/racing/pilot-at-training.jpg',
            ],
            'title' => "Let's Get Ready! Indoor seizoen 24/25",
        ],
        [
            'dateLabel' => '4 september 2022',
            'excerpt' => 'Een nieuw indoorseizoen in Alkmaar, met een vernieuwde aanpak voor de events.',
            'href' => 'https://dutchdronesquad.nl/here-we-go-indoor-seizoen-22-23/',
            'image' => [
                'alt' => 'Indoor FPV-track in het Sportpaleis in Alkmaar',
                'src' => '/images/dds/racing/indoor-track.jpg',
            ],
            'title' => 'Here we go! Indoor seizoen 22/23',
        ],
        [
            'dateLabel' => '13 augustus 2022',
            'excerpt' => 'Een gezamenlijke vliegmiddag en barbecue als afsluiting van de zomer.',
            'href' => 'https://dutchdronesquad.nl/bbq-2022/',
            'image' => [
                'alt' => 'Piloten bij elkaar tijdens een event van Dutch Drone Squad',
                'src' => '/images/dds/racing/training-community.jpg',
            ],
            'title' => 'BBQ: Fly to meat you 2022',
        ],
    ],
    'latestNewsAreLegacy' => true,
    'partnerLogos' => [
        [
            'alt' => 'Droneshop.nl',
            'href' => 'https://droneshop.nl',
            'src' => '/images/dds/partners/partner-droneshop.png',
        ],
    ],
];
