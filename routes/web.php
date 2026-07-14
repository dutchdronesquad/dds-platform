<?php

use App\Enums\Permission;
use App\Enums\Role;
use App\Http\Controllers\Admin\RedirectController;
use App\Http\Middleware\HandleLegacyRedirects;
use App\Support\SeoMetadata;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Spatie\Permission\Middleware\RoleMiddleware;

/** @var array<string, array<string, mixed>> $publicPages */
$publicPages = config('public_pages');

/** @var array<string, mixed> $homepage */
$homepage = config('homepage');

$seoMetadata = new SeoMetadata;

Route::inertia('/', 'welcome', [
    ...$homepage,
    'seo' => $seoMetadata->forPage('home'),
])->name('home');

Route::inertia('/events', 'public/shell', [
    'page' => $publicPages['events'],
    'seo' => $seoMetadata->forPage('events'),
])->name('events.index');

Route::get('/events/{slug}', function (SeoMetadata $seoMetadata, string $slug) {
    $title = Str::headline($slug);

    return Inertia::render('public/event-show', [
        'slug' => $slug,
        'seo' => $seoMetadata->forPage('event', [
            'title' => $title,
            'description' => "Bekijk de praktische informatie voor {$title}, een event van Dutch Drone Squad.",
            'canonical_path' => "/events/{$slug}",
        ]),
    ]);
})->name('events.show');

Route::inertia('/projects', 'public/shell', [
    'page' => $publicPages['projects'],
    'seo' => $seoMetadata->forPage('projects'),
])->name('projects.index');

Route::inertia('/news', 'public/shell', [
    'page' => $publicPages['news'],
    'seo' => $seoMetadata->forPage('news'),
])->name('news.index');

Route::inertia('/locations', 'public/shell', [
    'page' => $publicPages['locations'],
    'seo' => $seoMetadata->forPage('locations'),
])->name('locations.index');

Route::inertia('/about', 'public/shell', [
    'page' => $publicPages['about'],
    'seo' => $seoMetadata->forPage('about'),
])->name('about');

Route::inertia('/house-rules', 'public/shell', [
    'page' => $publicPages['house_rules'],
    'seo' => $seoMetadata->forPage('house_rules'),
])->name('house_rules');

Route::inertia('/partners', 'public/shell', [
    'page' => $publicPages['partners'],
    'seo' => $seoMetadata->forPage('partners'),
])->name('partners');

Route::inertia('/contact', 'public/shell', [
    'page' => $publicPages['contact'],
    'seo' => $seoMetadata->forPage('contact'),
])->name('contact');

Route::middleware([
    'auth',
    'verified',
    RoleMiddleware::using([Role::Admin, Role::Editor]),
])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    Route::get('dashboard/redirects', [RedirectController::class, 'index'])
        ->middleware('can:'.Permission::ViewRedirects->value)
        ->name('redirects.index');
});

require __DIR__.'/settings.php';

Route::fallback(fn () => abort(404))
    ->middleware(HandleLegacyRedirects::class);
