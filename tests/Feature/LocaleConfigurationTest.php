<?php

test('the application defaults to English', function () {
    expect(config('app.locale'))->toBe('en')
        ->and(app()->getLocale())->toBe('en');
});

test('supported locales are explicit', function () {
    expect(config('localization.supported_locales'))
        ->toHaveKeys(['en', 'nl'])
        ->and(config('localization.default_locale'))->toBe('en')
        ->and(config('localization.fallback_locale'))->toBe('en');
});

test('locale route prefixes are disabled', function () {
    expect(config('localization.use_locale_route_prefixes'))->toBeFalse();
});

test('translatable database content storage is documented in config', function () {
    expect(config('localization.translatable_content'))
        ->toMatchArray([
            'storage' => 'json_by_locale',
            'required_locale' => 'en',
            'fallback_locale' => 'en',
        ]);
});

test('frontend translation bundle shape is documented in config', function () {
    expect(config('localization.frontend_translations'))
        ->toMatchArray([
            'path_pattern' => 'resources/js/locales/{domain}/{locale}/{namespace}.json',
            'domains' => ['frontend', 'backend', 'global'],
            'namespace_separator' => '/',
        ]);
});
