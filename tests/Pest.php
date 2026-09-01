<?php

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithCachedConfig;
use Illuminate\Foundation\Testing\WithCachedRoutes;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

use function Pest\Laravel\withoutVite;

/*
|--------------------------------------------------------------------------
| Test Cases
|--------------------------------------------------------------------------
|
| Unit tests keep Pest's default PHPUnit test case so they stay isolated.
| Feature tests boot Laravel lazily and share cached framework bootstrap data.
| Browser tests keep eager database isolation for their real server requests.
|
*/

pest()->extend(TestCase::class)
    ->use(LazilyRefreshDatabase::class, WithCachedConfig::class, WithCachedRoutes::class)
    ->beforeEach(function () {
        config()->set('inertia.ssr.enabled', false);
        withoutVite();
        Http::preventStrayRequests();
    })
    ->in('Feature');

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Browser');

pest()->tia()
    ->baselined()
    ->filtered();
