<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Public project catalogue
    |--------------------------------------------------------------------------
    |
    | This catalogue contains only projects intended for public presentation.
    | Changes are reviewed and deployed through the normal pull-request flow.
    |
    */
    'projects' => [
        [
            'slug' => 'trackdraw',
            'title' => 'TrackDraw',
            'summary' => 'Ontwerp FPV-racebanen op schaal, beoordeel de racelijn in 3D en leg een bruikbaar baan- en opbouwplan vast voor de racedag.',
            'type' => 'application',
            'featured' => true,
            'video_url' => 'https://media.trackdraw.app/landing/video-demo.webm',
            'primary_link' => [
                'label' => 'Open de editor',
                'url' => 'https://trackdraw.app/studio',
            ],
            'supporting_links' => [
                [
                    'label' => 'Broncode',
                    'url' => 'https://github.com/dutchdronesquad/trackdraw',
                ],
                [
                    'label' => 'Roadmap',
                    'url' => 'https://github.com/dutchdronesquad/trackdraw/discussions/106',
                ],
            ],
            'credits' => [
                'Dutch Drone Squad',
                'Klaas Nicolaas en open-sourcebijdragers',
            ],
            'audience' => 'Race directors, clubs en organisatoren die een reproduceerbare baan en overdraagbaar opbouwplan nodig hebben.',
            'media' => [
                [
                    'path' => '/images/projects/trackdraw-mark-light.svg',
                    'dark_path' => '/images/projects/trackdraw-mark-dark.svg',
                    'alt' => 'TrackDraw-logo',
                ],
                [
                    'path' => '/images/projects/trackdraw-editor.webp',
                    'alt' => 'TrackDraw-editor met een uitgewerkte FPV-racebaan in de 3D-weergave',
                ],
            ],
        ],
        [
            'slug' => 'live-feed-flightcase',
            'title' => 'Live-feedkoffer',
            'summary' => 'Een mobiele live-feedopstelling rond de HDZero Event VRX waarmee videosignalen tijdens races centraal beschikbaar komen voor monitoring en productie.',
            'type' => 'hardware_build',
            'supporting_links' => [
                [
                    'label' => 'HDZero Event VRX-documentatie',
                    'url' => 'https://docs.hd-zero.com/event-introduction',
                ],
            ],
            'credits' => [
                'Ontwerp en bouw door Dutch Drone Squad',
                'Gebouwd rond de HDZero Event VRX',
            ],
            'audience' => 'Het DDS-team dat HDZero- en analoge live feeds betrouwbaar in de eigen eventproductie inzet.',
            'media' => [
                [
                    'path' => '/images/dds/racing/race-control.jpg',
                    'alt' => 'DDS-racecontrol met de mobiele live-feed- en regiekoffers',
                ],
            ],
        ],
        [
            'slug' => 'event-livestream-flightcase',
            'title' => 'Event-livestreamkoffer',
            'summary' => 'Een transporteerbare regieopstelling waarin schermen, bediening en eventtechniek samen in één flightcase naar de evenementlocatie gaan.',
            'type' => 'hardware_build',
            'supporting_links' => [],
            'credits' => [
                'Ontwerp en bouw door Dutch Drone Squad',
            ],
            'audience' => 'Het DDS-streamteam dat op verschillende locaties een herkenbare mobiele regieplek nodig heeft.',
            'media' => [
                [
                    'path' => '/images/dds/racing/race-control.jpg',
                    'alt' => 'DDS-racecontrol met de mobiele livestreamkoffer geopend op locatie',
                ],
            ],
        ],
        [
            'slug' => 'timing-flightcase',
            'title' => 'Tijdregistratiekoffer',
            'summary' => 'Een mobiele RotorHazard-opstelling waarin timingapparatuur en racebediening samen in één flightcase naar de evenementlocatie gaan.',
            'type' => 'hardware_build',
            'supporting_links' => [
                [
                    'label' => 'RotorHazard',
                    'url' => 'https://rotorhazard.com/',
                ],
            ],
            'credits' => [
                'Ontwerp en bouw door Dutch Drone Squad',
                'Gebouwd rond het open-source RotorHazard-platform',
            ],
            'audience' => 'Het DDS-raceteam dat betrouwbare tijdregistratie en racebediening mobiel inzet tijdens eigen events.',
            'media' => [
                [
                    'path' => '/images/dds/racing/race-control.jpg',
                    'alt' => 'DDS-racecontrol met de mobiele tijdregistratiekoffer',
                ],
            ],
        ],
        [
            'slug' => 'timer-dotfiles',
            'title' => 'Timer Dotfiles',
            'summary' => 'Scripts en configuratie voor het installeren, configureren en onderhouden van RotorHazard-timers voor ontwikkeling en wedstrijden.',
            'type' => 'race_tooling',
            'primary_link' => [
                'label' => 'Bekijk Timer Dotfiles',
                'url' => 'https://github.com/dutchdronesquad/timer-dotfiles',
            ],
            'supporting_links' => [
                [
                    'label' => 'Installatie en gebruik',
                    'url' => 'https://github.com/dutchdronesquad/timer-dotfiles#readme',
                ],
                [
                    'label' => 'RotorHazard',
                    'url' => 'https://github.com/RotorHazard/RotorHazard',
                ],
            ],
            'credits' => [
                'Ontwikkeld en gebruikt door Dutch Drone Squad',
                'Geïnspireerd door de RotorHazard-community',
            ],
            'audience' => 'Het DDS-team en andere RotorHazard-beheerders die timers voorspelbaar willen installeren, configureren en onderhouden.',
            'media' => [
                [
                    'path' => '/images/projects/timer-dotfiles-header.png',
                    'alt' => 'Timer Dotfiles voor het reproduceerbaar inrichten van een RotorHazard-timer',
                ],
            ],
        ],
        [
            'slug' => 'rotorhazard-contributions',
            'title' => 'Bijdragen aan RotorHazard',
            'summary' => 'Bijdragen aan RotorHazard, RHFest, de metadata-generator en de website waarmee communityplugins gevonden en geïnstalleerd kunnen worden.',
            'type' => 'open_source_contribution',
            'primary_link' => [
                'label' => 'Bekijk Community Plugins',
                'url' => 'https://rotorhazard.github.io/community-plugins/',
            ],
            'supporting_links' => [
                [
                    'label' => 'RotorHazard',
                    'url' => 'https://github.com/RotorHazard/RotorHazard',
                ],
                [
                    'label' => 'Community Plugins',
                    'url' => 'https://github.com/RotorHazard/community-plugins',
                ],
                [
                    'label' => 'RHFest Action',
                    'url' => 'https://github.com/RotorHazard/rhfest-action',
                ],
            ],
            'credits' => [
                'RotorHazard-community',
                'Bijdragen vanuit Dutch Drone Squad door Klaas Nicolaas',
            ],
            'audience' => 'RotorHazard-gebruikers en pluginontwikkelaars die uitbreidingen eenvoudiger willen publiceren, vinden, installeren en onderhouden.',
            'media' => [
                [
                    'path' => '/images/projects/rotorhazard-community-plugins.svg',
                    'alt' => 'Beeldmerk van RotorHazard Community Plugins',
                ],
            ],
        ],
        [
            'slug' => 'rh-stream-overlays',
            'title' => 'Stream Overlays',
            'summary' => 'Een verzameling race-overlays die RotorHazard-resultaten en wedstrijdinformatie direct bruikbaar maakt in OBS.',
            'type' => 'rotorhazard_plugin',
            'primary_link' => [
                'label' => 'Bekijk de overlays',
                'url' => 'https://overlays.dutchdronesquad.nl/overlays',
            ],
            'supporting_links' => [
                [
                    'label' => 'Documentatie',
                    'url' => 'https://overlays.dutchdronesquad.nl',
                ],
                [
                    'label' => 'Broncode',
                    'url' => 'https://github.com/dutchdronesquad/rh-stream-overlays',
                ],
            ],
            'credits' => [
                'Dutch Drone Squad en open-sourcebijdragers',
            ],
            'audience' => 'RotorHazard-organisatoren en streamteams die race-informatie professioneel in beeld willen brengen.',
            'media' => [
                [
                    'path' => '/images/projects/stream-overlays-mark.svg',
                    'alt' => 'Beeldmerk van Stream Overlays',
                ],
            ],
        ],
        [
            'slug' => 'rh-race-voice',
            'title' => 'Race Voice',
            'summary' => 'Lokale spraakoproepen en startgeluiden voor RotorHazard, met Piper TTS en gesynchroniseerde netwerkweergave via Sendspin.',
            'type' => 'rotorhazard_plugin',
            'primary_link' => [
                'label' => 'Download de laatste release',
                'url' => 'https://github.com/dutchdronesquad/rh-race-voice/releases/latest',
            ],
            'supporting_links' => [
                [
                    'label' => 'Gebruikshandleiding',
                    'url' => 'https://github.com/dutchdronesquad/rh-race-voice/blob/develop/docs/usage.md',
                ],
                [
                    'label' => 'Broncode',
                    'url' => 'https://github.com/dutchdronesquad/rh-race-voice',
                ],
            ],
            'credits' => [
                'Dutch Drone Squad en open-sourcebijdragers',
                'Piper TTS en het Sendspin-project',
            ],
            'audience' => 'Race directors en clubs die verstaanbare, consistente audio-oproepen op meerdere afspeelpunten nodig hebben.',
            'media' => [
                [
                    'path' => '/images/projects/race-voice-mark.svg',
                    'alt' => 'Beeldmerk van Race Voice',
                ],
            ],
        ],
        [
            'slug' => 'rh-youtube-chapters',
            'title' => 'YouTube Chapters',
            'summary' => 'Een RotorHazard-plugin die heatstarts vastlegt en omzet in hoofdstukken waarmee kijkers snel door een race-uitzending navigeren.',
            'type' => 'rotorhazard_plugin',
            'primary_link' => [
                'label' => 'Bekijk de plugin op GitHub',
                'url' => 'https://github.com/dutchdronesquad/rh-youtube-chapters',
            ],
            'supporting_links' => [
                [
                    'label' => 'RotorHazard Community Plugins',
                    'url' => 'https://rotorhazard.github.io/community-plugins/',
                ],
            ],
            'credits' => [
                'Ontwikkeld door Dutch Drone Squad',
                'Aangevraagd door Dutch Drone Racing',
            ],
            'audience' => 'Race-organisaties en videoteams die lange livestreamopnames toegankelijker willen maken voor terugkijkers.',
            'media' => [
                [
                    'path' => '/images/projects/youtube-chapters-mark.svg',
                    'alt' => 'Beeldmerk van YouTube Chapters',
                ],
            ],
        ],
    ],
];
