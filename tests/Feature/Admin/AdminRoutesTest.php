<?php

test('management links use the admin prefix and require authentication', function (string $name, string $path) {
    expect(route($name, [], false))->toBe($path);

    $this->get($path)->assertRedirect(route('login'));
})->with([
    'events' => ['admin.events.index', '/admin/events'],
    'media' => ['admin.media.index', '/admin/media'],
    'users' => ['admin.users.index', '/admin/users'],
    'roles' => ['admin.roles.index', '/admin/roles'],
    'redirects' => ['redirects.index', '/admin/redirects'],
]);

test('dashboard and personal settings keep their existing addresses', function (string $name, string $path) {
    expect(route($name, [], false))->toBe($path);
})->with([
    'dashboard' => ['dashboard', '/dashboard'],
    'profile' => ['profile.edit', '/settings/profile'],
]);
