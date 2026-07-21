<?php

use App\Enums\Permission;
use App\Enums\Role;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EventController as AdminEventController;
use App\Http\Controllers\Admin\EventStatusController;
use App\Http\Controllers\Admin\MediaAssetArchiveController;
use App\Http\Controllers\Admin\MediaAssetController;
use App\Http\Controllers\Admin\MediaAssetPickerController;
use App\Http\Controllers\Admin\RedirectController;
use App\Http\Controllers\Admin\RolePermissionController;
use App\Http\Controllers\Admin\SeasonController as AdminSeasonController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\UserStatusController;
use App\Http\Controllers\Public\EventController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\SeasonController;
use App\Http\Middleware\HandleLegacyRedirects;
use App\Support\SeoMetadata;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\RoleMiddleware;

/** @var array<string, array<string, mixed>> $publicPages */
$publicPages = config('public_pages');

$seoMetadata = new SeoMetadata;

Route::get('/', HomeController::class)->name('home');

Route::get('/events', [EventController::class, 'index'])->name('events.index');
Route::get('/events/{event:slug}', [EventController::class, 'show'])->name('events.show');
Route::get('/seasons/{season}', [SeasonController::class, 'show'])->name('seasons.show');

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
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::prefix('dashboard')->name('admin.')->group(function () {
        Route::get('media/picker', MediaAssetPickerController::class)
            ->name('media.picker');
        Route::patch('media/{mediaAsset}/archive', [MediaAssetArchiveController::class, 'archive'])
            ->name('media.archive');
        Route::patch('media/{mediaAsset}/restore', [MediaAssetArchiveController::class, 'restore'])
            ->name('media.restore');
        Route::resource('media', MediaAssetController::class)
            ->parameters(['media' => 'mediaAsset'])
            ->except('edit');
        Route::patch('events/{event}/publish', [EventStatusController::class, 'publish'])
            ->name('events.publish');
        Route::patch('events/{event}/unpublish', [EventStatusController::class, 'unpublish'])
            ->name('events.unpublish');
        Route::patch('events/{event}/cancel', [EventStatusController::class, 'cancel'])
            ->name('events.cancel');
        Route::resource('events', AdminEventController::class)->except('show');
        Route::resource('seasons', AdminSeasonController::class)->except('show');
        Route::patch('users/{user}/block', [UserStatusController::class, 'block'])
            ->name('users.block');
        Route::patch('users/{user}/unblock', [UserStatusController::class, 'unblock'])
            ->name('users.unblock');
        Route::resource('users', AdminUserController::class)->only(['index', 'edit', 'update', 'destroy']);
        Route::get('roles', RolePermissionController::class)
            ->middleware('can:'.Permission::ViewRoles->value)
            ->name('roles.index');
    });

    Route::get('dashboard/redirects', [RedirectController::class, 'index'])
        ->middleware('can:'.Permission::ViewRedirects->value)
        ->name('redirects.index');
});

require __DIR__.'/settings.php';

Route::fallback(fn () => abort(404))
    ->middleware(HandleLegacyRedirects::class);
