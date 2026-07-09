<?php

use App\Enums\Role;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Spatie\Permission\Middleware\RoleMiddleware;

/** @var array<string, array<string, mixed>> $publicPages */
$publicPages = config('public_pages');

Route::inertia('/', 'welcome')->name('home');

Route::inertia('/events', 'public/shell', [
    'page' => $publicPages['events'],
])->name('events.index');

Route::get('/events/{slug}', fn (string $slug) => Inertia::render('public/event-show', [
    'slug' => $slug,
]))->name('events.show');

Route::inertia('/projects', 'public/shell', [
    'page' => $publicPages['projects'],
])->name('projects.index');

Route::inertia('/news', 'public/shell', [
    'page' => $publicPages['news'],
])->name('news.index');

Route::inertia('/locations', 'public/shell', [
    'page' => $publicPages['locations'],
])->name('locations.index');

Route::inertia('/about', 'public/shell', [
    'page' => $publicPages['about'],
])->name('about');

Route::inertia('/house-rules', 'public/shell', [
    'page' => $publicPages['house_rules'],
])->name('house_rules');

Route::inertia('/partners', 'public/shell', [
    'page' => $publicPages['partners'],
])->name('partners');

Route::inertia('/contact', 'public/shell', [
    'page' => $publicPages['contact'],
])->name('contact');

Route::middleware([
    'auth',
    'verified',
    RoleMiddleware::using([Role::Admin, Role::Editor]),
])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
