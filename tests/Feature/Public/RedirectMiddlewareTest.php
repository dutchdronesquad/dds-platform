<?php

use App\Http\Middleware\HandleLegacyRedirects;
use App\Models\Redirect;
use Illuminate\Http\Request;

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

test('unsafe requests bypass legacy redirect lookup', function () {
    $response = app(HandleLegacyRedirects::class)->handle(
        Request::create('/submitted-form', 'POST'),
        fn () => response('continued', 418),
    );

    expect($response->getStatusCode())->toBe(418)
        ->and($response->getContent())->toBe('continued');
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

test('excessively long redirect chains are prevented', function () {
    $redirects = collect(range(1, 11))
        ->map(fn (int $position): Redirect => Redirect::factory()->create([
            'source_path' => "/chain-{$position}",
            'target_url' => $position === 11 ? '/news' : '/chain-'.($position + 1),
        ]));

    $this->get('/chain-1')->assertNotFound();

    expect($redirects->sum(fn (Redirect $redirect): int => $redirect->refresh()->hit_count))
        ->toBe(0);
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

test('malformed local redirect targets are ignored', function () {
    $redirect = Redirect::factory()->create([
        'source_path' => '/malformed-target',
        'target_url' => '/bad'.chr(0).'path',
    ]);

    $this->get('/malformed-target')->assertNotFound();

    expect($redirect->refresh()->hit_count)->toBe(0);
});
