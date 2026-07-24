<?php

use App\Enums\ProjectType;
use App\Support\ProjectCatalogue;
use App\Support\ProjectCatalogueEntry;

function validProjectCatalogueEntry(array $overrides = []): array
{
    return array_replace([
        'slug' => 'test-project',
        'title' => 'Test project',
        'summary' => 'A concise public summary.',
        'type' => ProjectType::CommunityUtility->value,
        'primary_link' => [
            'label' => 'View project',
            'url' => 'https://example.com/project',
        ],
        'supporting_links' => [],
        'credits' => ['Dutch Drone Squad'],
        'audience' => 'FPV pilots and race organisers.',
        'media' => [],
        'featured' => false,
    ], $overrides);
}

test('the configured public projects form a typed catalogue', function () {
    $catalogue = ProjectCatalogue::fromConfig();

    expect($catalogue->all())
        ->toHaveCount(9)
        ->each->toBeInstanceOf(ProjectCatalogueEntry::class)
        ->and($catalogue->find('trackdraw')?->type)->toBe(ProjectType::Application)
        ->and($catalogue->find('panevo'))->toBeNull()
        ->and($catalogue->find('live-feed-flightcase')?->type)->toBe(ProjectType::HardwareBuild)
        ->and($catalogue->find('timer-dotfiles')?->type)->toBe(ProjectType::RaceTooling)
        ->and($catalogue->find('rotorhazard-contributions')?->type)->toBe(ProjectType::OpenSourceContribution)
        ->and($catalogue->find('private-project'))->toBeNull();
});

test('exactly one project is featured on the public overview', function () {
    $featuredSlugs = collect(ProjectCatalogue::fromConfig()->all())
        ->filter(fn ($entry) => $entry->featured)
        ->pluck('slug');

    expect($featuredSlugs)->toEqual(collect(['trackdraw']));
});

test('the featured project has a demo video', function () {
    $trackdraw = ProjectCatalogue::fromConfig()->find('trackdraw');

    expect($trackdraw?->videoUrl)->toBe('https://media.trackdraw.app/landing/video-demo.webm')
        ->and(collect($trackdraw?->supportingLinks)->pluck('label'))
        ->not->toContain('Publieke galerij');
});

test('RotorHazard extensions are classified as plugins', function () {
    $catalogue = ProjectCatalogue::fromConfig();

    expect([
        $catalogue->find('rh-race-voice')?->type,
        $catalogue->find('rh-youtube-chapters')?->type,
        $catalogue->find('rh-stream-overlays')?->type,
    ])->each->toBe(ProjectType::RotorHazardPlugin);
});

test('DDS open-source work distinguishes owned tools from upstream contributions', function () {
    $catalogue = ProjectCatalogue::fromConfig();

    expect($catalogue->find('timer-dotfiles')?->type)->toBe(ProjectType::RaceTooling)
        ->and($catalogue->find('rotorhazard-contributions')?->type)->toBe(ProjectType::OpenSourceContribution)
        ->and($catalogue->find('rotorhazard-contributions')?->primaryLink['url'])
        ->toBe('https://rotorhazard.github.io/community-plugins/');
});

test('every configured project has public-facing context and valid media references', function () {
    foreach (ProjectCatalogue::fromConfig()->all() as $entry) {
        expect($entry->title)->not->toBeEmpty()
            ->and($entry->summary)->not->toBeEmpty()
            ->and($entry->audience)->not->toBeEmpty()
            ->and($entry->credits)->not->toBeEmpty()
            ->and($entry->type->label())->not->toBeEmpty()
            ->and($entry->primaryLink['url'])->toStartWith('https://');

        foreach ($entry->supportingLinks as $link) {
            expect($link['url'])->toStartWith('https://');
        }

        foreach ($entry->media as $medium) {
            expect(public_path(ltrim($medium['path'], '/')))->toBeFile();

            if (isset($medium['dark_path'])) {
                expect(public_path(ltrim($medium['dark_path'], '/')))->toBeFile();
            }
        }
    }
});

test('duplicate project slugs are rejected', function () {
    $entry = validProjectCatalogueEntry();

    expect(fn () => ProjectCatalogue::fromArray([$entry, $entry]))
        ->toThrow(InvalidArgumentException::class, 'Duplicate project slug [test-project].');
});

test('invalid catalogue fields are rejected', function (array $entry, string $message) {
    expect(fn () => ProjectCatalogue::fromArray([$entry]))
        ->toThrow(InvalidArgumentException::class, $message);
})->with([
    'missing summary' => [
        fn (): array => validProjectCatalogueEntry(['summary' => '']),
        'Project field [summary] must be a non-empty string.',
    ],
    'invalid slug' => [
        fn (): array => validProjectCatalogueEntry(['slug' => 'Not Safe']),
        'Project slug [Not Safe] must be a lowercase URL slug.',
    ],
    'unsupported type' => [
        fn (): array => validProjectCatalogueEntry(['type' => 'private']),
        'Project [test-project] has an unsupported type [private].',
    ],
    'missing audience' => [
        fn (): array => validProjectCatalogueEntry(['audience' => '']),
        'Project field [audience] must be a non-empty string.',
    ],
    'missing credits' => [
        fn (): array => validProjectCatalogueEntry(['credits' => []]),
        'Project [test-project] must credit at least one contributor or owner.',
    ],
    'unsafe primary link' => [
        fn (): array => validProjectCatalogueEntry([
            'primary_link' => [
                'label' => 'Unsafe',
                'url' => 'javascript:alert(1)',
            ],
        ]),
        'Project [test-project] field [primary_link] must use a safe HTTPS URL.',
    ],
    'unsafe supporting link' => [
        fn (): array => validProjectCatalogueEntry([
            'supporting_links' => [[
                'label' => 'Insecure',
                'url' => 'http://example.com',
            ]],
        ]),
        'Project [test-project] field [supporting_links] must use a safe HTTPS URL.',
    ],
    'external media path' => [
        fn (): array => validProjectCatalogueEntry([
            'media' => [[
                'path' => 'https://example.com/image.jpg',
                'alt' => 'Remote image',
            ]],
        ]),
        'Project [test-project] media must reference a versioned project image path.',
    ],
    'external dark media path' => [
        fn (): array => validProjectCatalogueEntry([
            'media' => [[
                'path' => '/images/projects/project.svg',
                'dark_path' => 'https://example.com/project-dark.svg',
                'alt' => 'Project logo',
            ]],
        ]),
        'Project [test-project] dark media must reference a versioned project image path.',
    ],
    'non-boolean featured flag' => [
        fn (): array => validProjectCatalogueEntry(['featured' => 'yes']),
        'Project [test-project] field [featured] must be a boolean.',
    ],
    'unsafe video url' => [
        fn (): array => validProjectCatalogueEntry(['video_url' => 'http://example.com/demo.webm']),
        'Project [test-project] field [video_url] must be a safe HTTPS link to a .webm or .mp4 video.',
    ],
    'non-video video url' => [
        fn (): array => validProjectCatalogueEntry(['video_url' => 'https://example.com/demo.mov']),
        'Project [test-project] field [video_url] must be a safe HTTPS link to a .webm or .mp4 video.',
    ],
]);
