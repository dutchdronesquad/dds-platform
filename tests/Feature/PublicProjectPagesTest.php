<?php

use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

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

    $projects = collect($response->inertiaProps('projects'));
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
