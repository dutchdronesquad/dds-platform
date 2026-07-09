<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

test('requests fall back to the configured default locale', function () {
    $this
        ->withHeader('Accept-Language', 'de-DE,de;q=0.9')
        ->get(route('home'))
        ->assertOk();

    expect(app()->getLocale())->toBe('en');
});

test('requests can resolve the locale from a guest cookie', function () {
    $this
        ->withUnencryptedCookie('locale', 'nl')
        ->get(route('home'))
        ->assertOk();

    expect(app()->getLocale())->toBe('nl');
});

test('unsupported guest locale cookies are ignored', function () {
    $this
        ->withUnencryptedCookie('locale', 'de')
        ->withHeader('Accept-Language', 'de-DE,de;q=0.9')
        ->get(route('home'))
        ->assertOk();

    expect(app()->getLocale())->toBe('en');
});

test('requests can resolve the locale from browser preference', function () {
    $this
        ->withHeader('Accept-Language', 'nl-NL,nl;q=0.9')
        ->get(route('home'))
        ->assertOk();

    expect(app()->getLocale())->toBe('nl');
});

test('authenticated user preference wins over guest cookie', function () {
    $user = User::factory()->create(['locale' => 'nl']);

    $this
        ->actingAs($user)
        ->withUnencryptedCookie('locale', 'en')
        ->get(route('home'))
        ->assertOk();

    expect(app()->getLocale())->toBe('nl');
});

test('inertia shares locale props', function () {
    $this
        ->withUnencryptedCookie('locale', 'nl')
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('locale.active', 'nl')
            ->where('locale.default', 'en')
            ->where('locale.fallback', 'en')
            ->where('locale.usesRoutePrefixes', false)
            ->has('locale.supported.en')
            ->has('locale.supported.nl'),
        );
});

test('guests can store a locale cookie', function () {
    $this
        ->from(route('home'))
        ->patch(route('locale.update'), ['locale' => 'nl'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('home'))
        ->assertPlainCookie('locale', 'nl');
});

test('authenticated users can store a persisted locale preference', function () {
    $user = User::factory()->create(['locale' => 'en']);

    $this
        ->actingAs($user)
        ->from(route('profile.edit'))
        ->patch(route('locale.update'), ['locale' => 'nl'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'))
        ->assertPlainCookie('locale', 'nl');

    expect($user->refresh()->locale)->toBe('nl')
        ->and($user->preferredLocale())->toBe('nl');
});

test('unsupported locale values are rejected', function () {
    $user = User::factory()->create(['locale' => 'en']);

    $this
        ->actingAs($user)
        ->from(route('profile.edit'))
        ->patch(route('locale.update'), ['locale' => 'de'])
        ->assertSessionHasErrors('locale')
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->locale)->toBe('en');
});
