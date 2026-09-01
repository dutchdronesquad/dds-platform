<?php

use Database\Seeders\RedirectSeeder;

beforeEach(function () {
    $this->seed(RedirectSeeder::class);
});

test('known legacy urls permanently redirect to their stable public target', function (string $sourcePath, string $targetUrl) {
    $this->get($sourcePath)
        ->assertMovedPermanently()
        ->assertRedirect($targetUrl);
})->with([
    'training page' => ['/trainingen/', '/events?type=training'],
    'training days alias' => ['/trainingsdagen/', '/events?type=training'],
    'agenda' => ['/agenda/', '/events'],
    'news archive' => ['/nieuws/', '/news'],
    'house rules' => ['/huisregels/', '/house-rules'],
]);
