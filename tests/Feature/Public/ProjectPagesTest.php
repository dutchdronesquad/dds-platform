<?php

use Inertia\Testing\AssertableInertia as Assert;

test('the project overview presents the curated public catalogue', function () {
    $response = $this->get(route('projects.index'));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/projects-index')
            ->has('projects', 9)
            ->missing('projects.0.hasCasePage')
            ->has('seo'),
        );

    $projectProperties = $response->inertiaProps('projects');

    expect(get_debug_type($projectProperties))->toBe('array');

    if (! is_array($projectProperties)) {
        return;
    }

    $projects = collect($projectProperties);
    $projectsBySlug = $projects->keyBy('slug');
    $slugs = $projects->pluck('slug');
    $trackdraw = $projectsBySlug['trackdraw'];

    expect($trackdraw['slug'])->toBe('trackdraw')
        ->and($trackdraw['media'])->toBe([
            [
                'src' => '/images/projects/trackdraw-mark-light.svg',
                'darkSrc' => '/images/projects/trackdraw-mark-dark.svg',
                'alt' => 'TrackDraw-logo',
            ],
            [
                'src' => '/images/projects/trackdraw-editor.webp',
                'alt' => 'TrackDraw-editor met een uitgewerkte FPV-racebaan in de 3D-weergave',
            ],
        ])
        ->and($trackdraw['videoUrl'])->toBe('https://media.trackdraw.app/landing/video-demo.webm')
        ->and($trackdraw['subprojects'])->toHaveCount(2)
        ->and($trackdraw['subprojects'][0]['title'])->toBe('Track Assets')
        ->and($trackdraw['subprojects'][1]['title'])->toBe('Track Viewer')
        ->and($trackdraw['subprojects'][0]['primaryLink']['url'])->toBe('https://designer.trackdraw.app')
        ->and($trackdraw['subprojects'][0]['media'][0]['src'])->toBe('/images/projects/track-assets-dds-preview.png')
        ->and($trackdraw['subprojects'][1]['media'][0]['src'])->toBe('/images/projects/track-viewer-preview.png')
        ->and($trackdraw['subprojects'][1]['primaryLink']['url'])->toBe('https://github.com/dutchdronesquad/track-viewer')
        ->and($projectsBySlug['timer-dotfiles']['subprojects'])->toBe([])
        ->and($trackdraw['featured'])->toBeTrue()
        ->and($projects->where('featured', true)->pluck('slug')->values()->all())->toBe(['trackdraw'])
        ->and($projects->where('featured', false))->toHaveCount(8)
        ->and($slugs->sort()->values()->all())->toBe([
            'event-livestream-flightcase',
            'live-feed-flightcase',
            'rh-race-voice',
            'rh-stream-overlays',
            'rh-youtube-chapters',
            'rotorhazard-contributions',
            'timer-dotfiles',
            'timing-flightcase',
            'trackdraw',
        ])
        ->and($projectsBySlug['timer-dotfiles']['type']['value'])->toBe('race_tooling')
        ->and($projectsBySlug['rotorhazard-contributions']['type']['value'])->toBe('open_source_contribution')
        ->and($projectsBySlug['rh-stream-overlays']['type']['value'])->toBe('rotorhazard_plugin')
        ->and($projectsBySlug['live-feed-flightcase']['primaryLink'])->toBeNull()
        ->and($projectsBySlug['event-livestream-flightcase']['primaryLink'])->toBeNull()
        ->and($projectsBySlug['timing-flightcase']['primaryLink'])->toBeNull()
        ->and($slugs)->not->toContain('panevo', 'private-project');
});

test('projects do not expose generic detail pages', function (string $slug) {
    $this->get("/projects/{$slug}")
        ->assertNotFound();
})->with([
    'TrackDraw' => ['trackdraw'],
    'Stream Overlays' => ['rh-stream-overlays'],
    'Live-feedkoffer' => ['live-feed-flightcase'],
    'Race Voice' => ['rh-race-voice'],
    'YouTube Chapters' => ['rh-youtube-chapters'],
    'Timer Dotfiles' => ['timer-dotfiles'],
    'RotorHazard contributions' => ['rotorhazard-contributions'],
    'unknown project' => ['bestaat-niet'],
]);
