<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InboxController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfileItemController;
use App\Http\Controllers\PublicProfileController;
use App\Http\Controllers\SharingController;
use App\Models\Profile;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('welcome', [
    'hasDemo' => Profile::query()->where('is_demo', true)->exists(),
]))->name('home');

Route::get('privacy', fn () => Inertia::render('legal/privacy'))->name('privacy');
Route::get('terms', fn () => Inertia::render('legal/terms'))->name('terms');
Route::get('demo', [PublicProfileController::class, 'demo'])->name('demo');

// The coach-facing page, opened with the athlete's secret link.
Route::prefix('p/{token}')->where(['token' => '[A-Za-z0-9]{1,64}'])->group(function () {
    Route::get('/', [PublicProfileController::class, 'show'])->middleware('throttle:share-view')->name('share.show');
    Route::get('photo', [PublicProfileController::class, 'photo'])->middleware('throttle:share-view')->name('share.photo');
    Route::post('contact', [PublicProfileController::class, 'contact'])->middleware('throttle:contact')->name('share.contact');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::get('profile/photo', [ProfileController::class, 'photo'])->name('profile.photo');
    Route::get('profile/preview', [SharingController::class, 'preview'])->name('profile.preview');

    Route::middleware('throttle:profile-write')->group(function () {
        Route::post('profile/photo', [ProfileController::class, 'storePhoto'])->middleware('throttle:uploads')->name('profile.photo.store');
        Route::delete('profile/photo', [ProfileController::class, 'destroyPhoto'])->name('profile.photo.destroy');

        Route::prefix('profile/items/{type}')->whereIn('type', ProfileItemController::TYPES)->group(function () {
            Route::post('/', [ProfileItemController::class, 'store'])->name('profile.items.store');
            Route::put('{id}', [ProfileItemController::class, 'update'])->whereNumber('id')->name('profile.items.update');
            Route::delete('{id}', [ProfileItemController::class, 'destroy'])->whereNumber('id')->name('profile.items.destroy');
            Route::post('{id}/move', [ProfileItemController::class, 'move'])->whereNumber('id')->name('profile.items.move');
        });

        Route::put('profile/{section}', [ProfileController::class, 'update'])->name('profile.update');

        Route::post('sharing/link', [SharingController::class, 'store'])->name('sharing.link.store');
        Route::delete('sharing/link', [SharingController::class, 'destroy'])->name('sharing.link.destroy');
        Route::put('sharing/visibility', [SharingController::class, 'visibility'])->name('sharing.visibility');

        Route::delete('inbox/{message}', [InboxController::class, 'destroy'])->whereNumber('message')->name('inbox.destroy');
    });

    Route::get('profile/{section}', [ProfileController::class, 'edit'])->name('profile.section');

    Route::get('sharing', [SharingController::class, 'index'])->name('sharing.index');

    Route::get('inbox', [InboxController::class, 'index'])->name('inbox.index');
    Route::get('inbox/{message}', [InboxController::class, 'show'])->whereNumber('message')->name('inbox.show');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
