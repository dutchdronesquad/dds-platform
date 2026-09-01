<?php

use App\Models\Redirect;

test('redirect paths are normalized independently from requests', function (string $path, string $expectedPath) {
    expect(Redirect::normalizePath($path))->toBe($expectedPath);
})->with([
    'root path' => ['/', '/'],
    'missing leading slash' => ['legacy/news', '/legacy/news'],
    'surrounding slashes' => ['///legacy/news///', '/legacy/news'],
    'absolute url' => ['https://example.com/legacy/news?campaign=summer', '/legacy/news'],
    'query string' => ['/legacy/news?campaign=summer', '/legacy/news'],
]);
