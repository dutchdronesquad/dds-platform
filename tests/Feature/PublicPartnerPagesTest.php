<?php

use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

test('the partner overview presents the verified catalogue', function () {
    $response = $this->get(route('partners'));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/partners-index')
            ->has('partners', 2)
            ->has('partners.0', fn (Assert $partner) => $partner
                ->where('key', 'droneshop-nl')
                ->where('name', 'Droneshop.nl')
                ->where('websiteUrl', 'https://droneshop.nl/')
                ->where('logoSrc', '/images/dds/partners/partner-droneshop.png')
                ->where('logoAlt', 'Droneshop.nl')
                ->where('description', 'Droneshop.nl ondersteunt DDS met de sponsoring van trackmateriaal voor onze trainingen en races.')
                ->missing('sortOrder')
                ->missing('showOnHomepage')
                ->missing('privateContactNote'))
            ->has('partners.1', fn (Assert $partner) => $partner
                ->where('key', 'sportpaleis-alkmaar')
                ->where('name', 'Sportpaleis Alkmaar')
                ->where('websiteUrl', 'https://sportpaleis-alkmaar.nl/')
                ->where('logoSrc', '/images/dds/partners/sportpaleis-alkmaar.svg')
                ->where('logoAlt', 'Logo van Sportpaleis Alkmaar')
                ->has('description'))
            ->has('seo'),
        );
});

test('the homepage uses the same catalogue and explicit visibility', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('welcome')
            ->has('partners', 2)
            ->where('partners.0.key', 'droneshop-nl')
            ->where('partners.1.key', 'sportpaleis-alkmaar')
            ->missing('partnerLogos'),
        );
});
