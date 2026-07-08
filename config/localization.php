<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Supported Locales
    |--------------------------------------------------------------------------
    |
    | DDS publishes content in English and Dutch. The public route structure
    | stays unprefixed in phase 1; locale choice is a content concern, not a
    | URL segment concern.
    |
    */

    'supported_locales' => [
        'en' => [
            'name' => 'English',
            'native_name' => 'English',
        ],
        'nl' => [
            'name' => 'Nederlands',
            'native_name' => 'Nederlands',
        ],
    ],

    'default_locale' => env('APP_LOCALE', 'en'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'use_locale_route_prefixes' => false,

    'cookie' => [
        'name' => 'locale',
        'duration_minutes' => 60 * 24 * 365,
    ],

    'user_preference' => [
        'enabled' => true,
        'column' => 'locale',
    ],

    /*
    |--------------------------------------------------------------------------
    | Translatable Content Fields
    |--------------------------------------------------------------------------
    |
    | Phase 1 stores translatable content in JSON columns keyed by locale, for
    | example: {"en": "Title", "nl": "Titel"}. Admin forms should render one
    | field per supported locale for these attributes and persist the combined
    | locale map back to the JSON column.
    |
    */

    'translatable_content' => [
        'storage' => 'json_by_locale',
        'required_locale' => 'en',
        'fallback_locale' => 'en',
    ],

    /*
    |--------------------------------------------------------------------------
    | Frontend Translation Bundles
    |--------------------------------------------------------------------------
    |
    | When React UI strings become translatable, store JSON bundles using this
    | shape: resources/js/locales/{domain}/{locale}/{namespace}.json.
    | Examples: frontend/nl/navigation.json, backend/en/settings.json,
    | global/nl/validation.json.
    |
    */

    'frontend_translations' => [
        'path_pattern' => 'resources/js/locales/{domain}/{locale}/{namespace}.json',
        'domains' => ['frontend', 'backend', 'global'],
        'namespace_separator' => '/',
    ],

];
