<?php

use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

test('the house rules page contains the approved legacy safety rules', function () {
    $this->get(route('house_rules'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/house-rules')
            ->has('seo'));
});

test('the media overview provides the approved chronological archive', function () {
    $this->get(route('media'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/media')
            ->has('seo'));

    expect([
        public_path('images/dds/media/zondagskrant-alkmaar-2019.png'),
        public_path('images/dds/media/quadinsider-magazine-2018.jpg'),
        public_path('images/dds/media/noordhollands-dagblad-2018.jpg'),
        public_path('images/dds/media/youtube-rc-playground-2023.jpg'),
        public_path('images/dds/media/youtube-zapplive-drone-racer.jpg'),
        public_path('images/dds/media/youtube-dds-february-2024.jpg'),
        public_path('images/dds/media/youtube-fmf-alkmaar-2019.jpg'),
        public_path('images/dds/media/video-streekstad-centraal-2019.jpg'),
    ])->each->toBeFile();
});
