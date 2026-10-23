<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('pages receive the version of their deployed build', function (string $version) {
    config(['app.version' => $version]);

    $this->actingAs(User::factory()->create())
        ->get(route('profile.edit'))
        ->assertInertia(fn (Assert $page) => $page->where('releaseVersion', $version));
})->with(['v1.4.0', 'v1.3.0', 'development']);
