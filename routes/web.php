<?php

use App\Enums\Permission;
use App\Enums\Role;
use App\Http\Controllers\Admin\ArticleController as AdminArticleController;
use App\Http\Controllers\Admin\ContactController as AdminContactController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EventController as AdminEventController;
use App\Http\Controllers\Admin\EventStatusController;
use App\Http\Controllers\Admin\LocationAddressLookupController;
use App\Http\Controllers\Admin\LocationAddressSuggestController;
use App\Http\Controllers\Admin\LocationController as AdminLocationController;
use App\Http\Controllers\Admin\MediaAssetArchiveController;
use App\Http\Controllers\Admin\MediaAssetController;
use App\Http\Controllers\Admin\MediaAssetPickerController;
use App\Http\Controllers\Admin\MediaAssetQuickUploadController;
use App\Http\Controllers\Admin\RedirectController;
use App\Http\Controllers\Admin\RolePermissionController;
use App\Http\Controllers\Admin\SeasonController as AdminSeasonController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\UserStatusController;
use App\Http\Controllers\Public\ArticleController;
use App\Http\Controllers\Public\ContactController as PublicContactController;
use App\Http\Controllers\Public\EventController;
use App\Http\Controllers\Public\GettingStartedController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\LocationController;
use App\Http\Controllers\Public\PartnerController;
use App\Http\Controllers\Public\ProjectController;
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

Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');

Route::get('/news', [ArticleController::class, 'index'])->name('news.index');
Route::get('/news/{article:slug}', [ArticleController::class, 'show'])->name('news.show');

Route::get('/locations', [LocationController::class, 'index'])->name('locations.index');
Route::get('/locations/{location:slug}', [LocationController::class, 'show'])->name('locations.show');

Route::get('/getting-started', [GettingStartedController::class, 'index'])->name('getting_started.index');
Route::get('/getting-started/{guide}', [GettingStartedController::class, 'show'])->name('getting_started.show');

Route::inertia('/about', 'public/shell', [
    'page' => $publicPages['about'],
    'seo' => $seoMetadata->forPage('about'),
])->name('about');

Route::inertia('/house-rules', 'public/shell', [
    'page' => $publicPages['house_rules'],
    'seo' => $seoMetadata->forPage('house_rules'),
])->name('house_rules');

Route::get('/partners', [PartnerController::class, 'index'])->name('partners');

Route::get('/contact', [PublicContactController::class, 'index'])->name('contact');
Route::post('/contact', [PublicContactController::class, 'store'])
    ->middleware('throttle:contact-submissions')
    ->name('contact.store');

Route::middleware([
    'auth',
    'verified',
    RoleMiddleware::using([Role::Admin, Role::Editor]),
])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::prefix('dashboard')->name('admin.')->group(function () {
        Route::get('media/picker', MediaAssetPickerController::class)
            ->name('media.picker');
        Route::post('media/quick-upload', MediaAssetQuickUploadController::class)
            ->name('media.quick-upload');
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
        Route::get('locations/address-suggestions', LocationAddressSuggestController::class)
            ->middleware('throttle:location-geocoding')
            ->name('locations.address-suggestions');
        Route::get('locations/lookup-address', LocationAddressLookupController::class)
            ->middleware('throttle:location-geocoding')
            ->name('locations.lookup-address');
        Route::resource('locations', AdminLocationController::class)->except('show');
        Route::resource('articles', AdminArticleController::class)->except('show');
        Route::resource('seasons', AdminSeasonController::class)->except('show');
        Route::resource('contact-submissions', AdminContactController::class)
            ->parameters(['contact-submissions' => 'contactSubmission'])
            ->only(['index', 'show'])
            ->names([
                'index' => 'contact.index',
                'show' => 'contact.show',
            ])
            ->middleware('can:'.Permission::ViewContact->value);
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
