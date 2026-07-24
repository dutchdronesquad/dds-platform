<?php

use App\Support\PartnerCatalogue;
use App\Support\PartnerCatalogueEntry;

function validPartnerCatalogueEntry(array $overrides = []): array
{
    return array_replace([
        'key' => 'test-partner',
        'name' => 'Test Partner',
        'website_url' => 'https://example.com/',
        'logo_path' => '/images/dds/partners/partner-droneshop.png',
        'logo_alt' => 'Logo van Test Partner',
        'description' => 'Een publieke partnerbeschrijving.',
        'sort_order' => 10,
        'show_on_homepage' => true,
    ], $overrides);
}

test('the configured partners form a typed and ordered public catalogue', function () {
    $catalogue = PartnerCatalogue::fromConfig();

    expect($catalogue->all())
        ->toHaveCount(2)
        ->each->toBeInstanceOf(PartnerCatalogueEntry::class)
        ->and(collect($catalogue->all())->pluck('key')->all())->toBe([
            'droneshop-nl',
            'sportpaleis-alkmaar',
        ])
        ->and($catalogue->find('droneshop-nl')?->websiteUrl)->toBe('https://droneshop.nl/')
        ->and($catalogue->find('sportpaleis-alkmaar')?->websiteUrl)->toBe('https://sportpaleis-alkmaar.nl/')
        ->and($catalogue->find('private-partner'))->toBeNull();
});

test('homepage visibility is explicit and keeps catalogue order', function () {
    $catalogue = PartnerCatalogue::fromArray([
        validPartnerCatalogueEntry([
            'key' => 'later-partner',
            'sort_order' => 20,
            'show_on_homepage' => true,
        ]),
        validPartnerCatalogueEntry([
            'key' => 'hidden-partner',
            'sort_order' => 15,
            'show_on_homepage' => false,
        ]),
        validPartnerCatalogueEntry([
            'key' => 'first-partner',
            'sort_order' => 10,
            'show_on_homepage' => true,
        ]),
    ]);

    expect(collect($catalogue->all())->pluck('key')->all())->toBe([
        'first-partner',
        'hidden-partner',
        'later-partner',
    ])->and(collect($catalogue->forHomepage())->pluck('key')->all())->toBe([
        'first-partner',
        'later-partner',
    ]);
});

test('equal manual positions are ordered deterministically by key', function () {
    $catalogue = PartnerCatalogue::fromArray([
        validPartnerCatalogueEntry(['key' => 'zeta-partner']),
        validPartnerCatalogueEntry(['key' => 'alpha-partner']),
    ]);

    expect(collect($catalogue->all())->pluck('key')->all())->toBe([
        'alpha-partner',
        'zeta-partner',
    ]);
});

test('an empty partner catalogue is valid', function () {
    $catalogue = PartnerCatalogue::fromArray([]);

    expect($catalogue->all())->toBe([])
        ->and($catalogue->forHomepage())->toBe([])
        ->and($catalogue->find('missing-partner'))->toBeNull();
});

test('every configured partner references a committed recognizable logo', function () {
    foreach (PartnerCatalogue::fromConfig()->all() as $partner) {
        expect($partner->name)->not->toBeEmpty()
            ->and($partner->logoAlt)->not->toBeEmpty()
            ->and($partner->websiteUrl)->toStartWith('https://')
            ->and(public_path(ltrim($partner->logoPath, '/')))->toBeFile();
    }
});

test('duplicate partner keys are rejected', function () {
    $entry = validPartnerCatalogueEntry();

    expect(fn () => PartnerCatalogue::fromArray([$entry, $entry]))
        ->toThrow(InvalidArgumentException::class, 'Duplicate partner key [test-partner].');
});

test('invalid or private catalogue fields are rejected', function (array $entry, string $message) {
    expect(fn () => PartnerCatalogue::fromArray([$entry]))
        ->toThrow(InvalidArgumentException::class, $message);
})->with([
    'invalid key' => [
        fn (): array => validPartnerCatalogueEntry(['key' => 'Not Safe']),
        'Partner key [Not Safe] must be a lowercase URL-safe key.',
    ],
    'missing name' => [
        fn (): array => validPartnerCatalogueEntry(['name' => '']),
        'Partner field [name] must be a non-empty string.',
    ],
    'unsafe website' => [
        fn (): array => validPartnerCatalogueEntry(['website_url' => 'javascript:alert(1)']),
        'Partner [test-partner] field [website_url] must use a safe HTTPS URL.',
    ],
    'insecure website' => [
        fn (): array => validPartnerCatalogueEntry(['website_url' => 'http://example.com']),
        'Partner [test-partner] field [website_url] must use a safe HTTPS URL.',
    ],
    'external logo' => [
        fn (): array => validPartnerCatalogueEntry(['logo_path' => 'https://example.com/logo.svg']),
        'Partner [test-partner] field [logo_path] must reference a versioned partner image.',
    ],
    'missing logo' => [
        fn (): array => validPartnerCatalogueEntry(['logo_path' => '/images/dds/partners/missing.svg']),
        'Partner [test-partner] logo asset [/images/dds/partners/missing.svg] does not exist.',
    ],
    'missing logo text' => [
        fn (): array => validPartnerCatalogueEntry(['logo_alt' => '']),
        'Partner field [logo_alt] must be a non-empty string.',
    ],
    'empty description' => [
        fn (): array => validPartnerCatalogueEntry(['description' => '']),
        'Partner [test-partner] field [description] must be a non-empty string or null.',
    ],
    'invalid order' => [
        fn (): array => validPartnerCatalogueEntry(['sort_order' => -1]),
        'Partner [test-partner] field [sort_order] must be a non-negative integer.',
    ],
    'implicit homepage visibility' => [
        function (): array {
            $entry = validPartnerCatalogueEntry();
            unset($entry['show_on_homepage']);

            return $entry;
        },
        'Partner [test-partner] field [show_on_homepage] must be a boolean.',
    ],
    'private contact note' => [
        fn (): array => validPartnerCatalogueEntry(['private_contact_note' => 'Niet publiceren']),
        'Partner catalogue field [private_contact_note] is not public and is not supported.',
    ],
]);
