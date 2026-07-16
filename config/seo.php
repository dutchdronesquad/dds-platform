<?php

return [
    'defaults' => [
        'title' => 'Dutch Drone Squad',
        'description' => 'Dutch Drone Squad brengt FPV-piloten, makers en partners samen rond indoor drone racing in Alkmaar.',
        'canonical_path' => '/',
        'robots' => 'index, follow',
        'image_path' => '/images/dds/racing/homepage-hero.jpg',
        'image_alt' => 'FPV-drone op het indoor raceparcours van Dutch Drone Squad',
        'open_graph_type' => 'website',
        'site_name' => 'Dutch Drone Squad',
    ],

    'pages' => [
        'home' => [
            'title' => 'Indoor FPV-racing in Alkmaar',
            'description' => 'Race op een volledig indoor FPV-parcours in Alkmaar, verbeter je rondetijden en ontmoet andere dronepiloten bij Dutch Drone Squad.',
            'canonical_path' => '/',
        ],
        'events' => [
            'title' => 'Agenda',
            'description' => 'Bekijk trainingen, races, demo-vluchten en andere activiteiten van Dutch Drone Squad.',
            'canonical_path' => '/events',
            'image_path' => '/images/dds/racing/indoor-track.jpg',
            'image_alt' => 'Indoor FPV-raceparcours van Dutch Drone Squad in Alkmaar',
        ],
        'event' => [
            'title' => 'Event',
            'description' => 'Bekijk de praktische informatie voor dit event van Dutch Drone Squad.',
            'canonical_path' => '/events',
            'image_path' => '/images/dds/racing/indoor-track.jpg',
            'image_alt' => 'Indoor FPV-raceparcours van Dutch Drone Squad in Alkmaar',
        ],
        'season' => [
            'title' => 'Seizoen',
            'description' => 'Bekijk alle events en ticketinformatie voor dit seizoen van Dutch Drone Squad.',
            'canonical_path' => '/seasons',
            'image_path' => '/images/dds/racing/indoor-track.jpg',
            'image_alt' => 'Indoor FPV-raceparcours van Dutch Drone Squad in Alkmaar',
        ],
        'projects' => [
            'title' => 'Projecten',
            'description' => 'Ontdek tooling, software, integraties en community-builds van Dutch Drone Squad.',
            'canonical_path' => '/projects',
            'image_path' => '/images/dds/racing/pilot-preparing-drone.jpg',
            'image_alt' => 'FPV-piloot werkt aan een racedrone tijdens een DDS-training',
        ],
        'news' => [
            'title' => 'Nieuws',
            'description' => 'Lees aankondigingen, eventverslagen en community-updates van Dutch Drone Squad.',
            'canonical_path' => '/news',
            'image_path' => '/images/dds/racing/pilot-at-training.jpg',
            'image_alt' => 'Piloot tijdens een indoor training van Dutch Drone Squad',
        ],
        'locations' => [
            'title' => 'Locaties',
            'description' => 'Bekijk de vlieg- en eventlocaties van Dutch Drone Squad en de praktische informatie per locatie.',
            'canonical_path' => '/locations',
            'image_path' => '/images/dds/locations/sporthal-koggenhal.jpg',
            'image_alt' => 'Sporthal waar Dutch Drone Squad activiteiten organiseert',
        ],
        'about' => [
            'title' => 'Over DDS',
            'description' => 'Lees hoe Dutch Drone Squad dronepiloten, makers en partners samenbrengt rond veilig vliegen, kennisdeling en FPV-racing.',
            'canonical_path' => '/about',
            'image_path' => '/images/dds/racing/training-community.jpg',
            'image_alt' => 'Piloten en bezoekers tijdens een event van Dutch Drone Squad',
        ],
        'house_rules' => [
            'title' => 'Huisregels',
            'description' => 'Lees de afspraken voor veilige, respectvolle deelname aan activiteiten van Dutch Drone Squad.',
            'canonical_path' => '/house-rules',
            'image_path' => '/images/dds/racing/pilot-preparing-drone.jpg',
            'image_alt' => 'FPV-piloot bereidt veilig een racedrone voor',
        ],
        'partners' => [
            'title' => 'Partners',
            'description' => 'Ontdek de partners die bijdragen aan events, kennisdeling, tooling en locaties van Dutch Drone Squad.',
            'canonical_path' => '/partners',
            'image_path' => '/images/dds/racing/race-control.jpg',
            'image_alt' => 'Race control tijdens een activiteit van Dutch Drone Squad',
        ],
        'contact' => [
            'title' => 'Contact',
            'description' => 'Neem contact op met Dutch Drone Squad over deelname, events, projecten, pers of partnerschappen.',
            'canonical_path' => '/contact',
            'image_path' => '/images/dds/racing/pilot-at-training.jpg',
            'image_alt' => 'FPV-piloot bij een trainingsavond van Dutch Drone Squad',
        ],
    ],
];
