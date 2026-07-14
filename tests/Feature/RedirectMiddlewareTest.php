<?php

use App\Models\Redirect;

test('active redirects match exact paths and increment their hit count', function () {
    $redirect = Redirect::factory()->create([
        'source_path' => '/old-page/',
        'target_url' => '/news/new-page',
    ]);

    $this->get('/old-page/')
        ->assertMovedPermanently()
        ->assertRedirect('/news/new-page');

    expect($redirect->refresh()->hit_count)->toBe(1);
});

test('redirect targets preserve their query string', function () {
    Redirect::factory()->create([
        'source_path' => '/training-days',
        'target_url' => '/events?type=training',
    ]);

    $this->get('/training-days')
        ->assertMovedPermanently()
        ->assertRedirect('/events?type=training');
});

test('inactive redirects are ignored', function () {
    Redirect::factory()->inactive()->create([
        'source_path' => '/inactive-page',
        'target_url' => '/news',
    ]);

    $this->get('/inactive-page')->assertNotFound();
});

test('redirects do not match descendant paths', function () {
    Redirect::factory()->create([
        'source_path' => '/legacy',
        'target_url' => '/news',
    ]);

    $this->get('/legacy/child')->assertNotFound();
});

test('self-referencing redirects are prevented', function () {
    $redirect = Redirect::factory()->create([
        'source_path' => '/loop',
        'target_url' => '/loop',
    ]);

    $this->get('/loop')->assertNotFound();

    expect($redirect->refresh()->hit_count)->toBe(0);
});

test('multi-step redirect loops are prevented', function () {
    $firstRedirect = Redirect::factory()->create([
        'source_path' => '/first-loop',
        'target_url' => '/second-loop',
    ]);
    $secondRedirect = Redirect::factory()->create([
        'source_path' => '/second-loop',
        'target_url' => '/first-loop',
    ]);

    $this->get('/first-loop')->assertNotFound();

    expect($firstRedirect->refresh()->hit_count)->toBe(0)
        ->and($secondRedirect->refresh()->hit_count)->toBe(0);
});

test('external redirect targets are supported', function () {
    Redirect::factory()->create([
        'source_path' => '/partner',
        'target_url' => 'https://example.com/partner',
        'status_code' => 302,
    ]);

    $this->get('/partner')
        ->assertFound()
        ->assertRedirect('https://example.com/partner');
});
