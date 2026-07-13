<?php

/**
 * Temporary public shell page content.
 *
 * This keeps placeholder copy out of route definitions until these pages move
 * to first-class models or constrained managed content.
 */
return [
    'events' => [
        'title' => 'Events',
        'eyebrow' => 'Agenda',
        'description' => 'Clubdagen, trainingen, demo-vluchten en communitybijeenkomsten krijgen hier een vaste publieke plek.',
        'visual' => [
            'src' => '/images/dds/indoor-track.jpg',
            'alt' => 'Indoor FPV-raceparcours van Dutch Drone Squad in Alkmaar',
            'position' => '56% center',
        ],
        'primaryAction' => [
            'label' => 'Bekijk projecten',
            'href' => '/projects',
        ],
        'sections' => [
            [
                'heading' => 'Wat hier komt',
                'body' => 'Een overzicht van aankomende DDS-activiteiten met ruimte voor type, locatie en praktische deelname-informatie.',
            ],
            [
                'heading' => 'Voor bezoekers',
                'body' => 'Deze pagina helpt leden, nieuwe vliegers en partners snel te zien waar DDS zichtbaar is.',
            ],
        ],
    ],
    'projects' => [
        'title' => 'Projects',
        'eyebrow' => 'Showcase',
        'description' => 'Publieke showcase voor DDS-built tooling, software, plugins, apps, integraties en geselecteerde community builds.',
        'visual' => [
            'src' => '/images/dds/pilot-preparing-drone.jpg',
            'alt' => 'FPV-piloot werkt aan een racedrone tijdens een DDS-training',
            'position' => '44% center',
        ],
        'primaryAction' => [
            'label' => 'Neem contact op',
            'href' => '/contact',
        ],
        'sections' => [
            [
                'heading' => 'Geen intern projectbeheer',
                'body' => 'Projects toont straks wat de community bouwt en gebruikt, zodat bezoekers de resultaten kunnen bekijken zonder toegang tot interne planning.',
            ],
            [
                'heading' => 'Selectiecriteria',
                'body' => 'De focus ligt op bruikbare tooling, documentatie, integraties en builds die de dronecommunity verder helpen.',
            ],
        ],
    ],
    'news' => [
        'title' => 'News',
        'eyebrow' => 'Updates',
        'description' => 'Publieke updates, aankondigingen en terugblikken vormen straks de tijdlijn van Dutch Drone Squad.',
        'visual' => [
            'src' => '/images/dds/pilot-at-training.jpg',
            'alt' => 'Pilot tijdens een indoor trainingsavond van Dutch Drone Squad',
            'position' => '62% center',
        ],
        'primaryAction' => [
            'label' => 'Bekijk events',
            'href' => '/events',
        ],
        'sections' => [
            [
                'heading' => 'Redactionele richting',
                'body' => 'Nieuwsitems kunnen korte community-updates, eventverslagen, releases en partnerupdates bundelen.',
            ],
            [
                'heading' => 'Volgende stap',
                'body' => 'De shell is klaar voor import of handmatige invoer zodra het contentmodel wordt toegevoegd.',
            ],
        ],
    ],
    'locations' => [
        'title' => 'Locations',
        'eyebrow' => 'Vliegplekken',
        'description' => 'Locaties verzamelen straks clubplekken, eventlocaties en relevante regionale context voor activiteiten.',
        'visual' => [
            'src' => '/images/dds/indoor-track.jpg',
            'alt' => 'Indoor trainingslocatie met FPV-raceparcours',
            'position' => '50% center',
        ],
        'primaryAction' => [
            'label' => 'Bekijk huisregels',
            'href' => '/house-rules',
        ],
        'sections' => [
            [
                'heading' => 'Locatie-informatie',
                'body' => 'Elke locatie kan later praktische gegevens, aandachtspunten en gekoppelde events tonen.',
            ],
            [
                'heading' => 'Veiligheidscontext',
                'body' => 'De pagina maakt ruimte voor heldere verwijzingen naar regels, afspraken en lokale beperkingen.',
            ],
        ],
    ],
    'about' => [
        'title' => 'About',
        'eyebrow' => 'Dutch Drone Squad',
        'description' => 'Dutch Drone Squad brengt dronevliegers, makers en partners samen rond veilig vliegen, kennisdeling en zichtbare communityprojecten.',
        'visual' => [
            'src' => '/images/dds/pilot-at-training.jpg',
            'alt' => 'Lid van Dutch Drone Squad vliegt tijdens een indoor training',
            'position' => '62% center',
        ],
        'primaryAction' => [
            'label' => 'Lees de huisregels',
            'href' => '/house-rules',
        ],
        'sections' => [
            [
                'heading' => 'Community',
                'body' => 'DDS is de publieke plek waar activiteiten, projecten en praktische informatie samenkomen.',
            ],
            [
                'heading' => 'Doelgroep',
                'body' => 'De site bedient leden, geïnteresseerde vliegers, partners en bezoekers die willen begrijpen waar DDS voor staat.',
            ],
        ],
    ],
    'house_rules' => [
        'title' => 'House Rules',
        'eyebrow' => 'Afspraken',
        'description' => 'De huisregels geven straks duidelijke verwachtingen voor deelname, veiligheid en gedrag binnen de DDS-community.',
        'visual' => [
            'src' => '/images/dds/pilot-preparing-drone.jpg',
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
                'body' => 'Deze pagina krijgt ruimte voor basisafspraken rond vliegen, privacy, materiaal en omgang met elkaar.',
            ],
            [
                'heading' => 'Praktische toepassing',
                'body' => 'Huisregels kunnen later gekoppeld worden aan events, locaties en onboardingmateriaal.',
            ],
        ],
    ],
    'partners' => [
        'title' => 'Partners',
        'eyebrow' => 'Samenwerking',
        'description' => 'Partners krijgen straks een herkenbare plek voor samenwerkingen, bijdragen en community-initiatieven.',
        'visual' => [
            'src' => '/images/dds/indoor-track.jpg',
            'alt' => 'Professioneel indoor FPV-raceparcours voor DDS-events en partners',
            'position' => '56% center',
        ],
        'primaryAction' => [
            'label' => 'Start contact',
            'href' => '/contact',
        ],
        'sections' => [
            [
                'heading' => 'Samen bouwen',
                'body' => 'Deze shell ondersteunt partners die bijdragen aan events, kennisdeling, tooling of locaties.',
            ],
            [
                'heading' => 'Publieke zichtbaarheid',
                'body' => 'De pagina kan later partnerprofielen, cases en concrete call-to-actions tonen.',
            ],
        ],
    ],
    'contact' => [
        'title' => 'Contact',
        'eyebrow' => 'Bereik DDS',
        'description' => 'Contact wordt de publieke route voor vragen over events, projecten, partnerschappen en deelname aan Dutch Drone Squad.',
        'visual' => [
            'src' => '/images/dds/pilot-at-training.jpg',
            'alt' => 'FPV-piloot bij een trainingsavond van Dutch Drone Squad',
            'position' => '62% center',
        ],
        'primaryAction' => [
            'label' => 'Bekijk events',
            'href' => '/events',
        ],
        'sections' => [
            [
                'heading' => 'Waarvoor contact',
                'body' => 'Bezoekers kunnen hier straks de juiste route vinden voor deelname, samenwerking, persvragen of projectvoorstellen.',
            ],
            [
                'heading' => 'Vervolg',
                'body' => 'Een formulier of contactblok kan later worden toegevoegd zonder de publieke URL-structuur te wijzigen.',
            ],
        ],
    ],
];
