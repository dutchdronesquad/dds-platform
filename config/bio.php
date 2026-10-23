<?php

return [
    /*
     * Entries use a root-relative href or an HTTPS URL. Array order controls
     * display order. Optional note and image provide campaign copy/artwork.
     * starts_at and ends_at use ISO 8601 with an explicit offset, for example
     * 2026-11-01T18:00:00+01:00 (Amsterdam winter time; +02:00 in summer).
     * Starts are inclusive; ends are exclusive. Null means no boundary.
     * A featured entry replaces the newest news card while it is active.
     */
    'featured' => null,

    'links' => [
        ['title' => 'Bekijk de agenda', 'href' => '/events'],
        ['title' => 'Starten met FPV', 'href' => '/getting-started'],
        ['title' => 'Onze community', 'href' => '/contact#community'],
        ['title' => 'Contact', 'href' => '/contact'],
    ],

    'socials' => [
        ['title' => 'Instagram', 'href' => 'https://www.instagram.com/dutchdronesquad/'],
        ['title' => 'YouTube', 'href' => 'https://www.youtube.com/@dutchdronesquad'],
        ['title' => 'Facebook', 'href' => 'https://www.facebook.com/dutchdronesquad'],
        ['title' => 'Twitch', 'href' => 'https://www.twitch.tv/dutchdronesquad'],
    ],
];
