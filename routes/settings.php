<?php

use App\Http\Controllers\Settings\DataController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\TwoFactorController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->group(function () {
    Route::redirect('settings', 'settings/account');

    Route::get('settings/account', [ProfileController::class, 'edit'])->name('account.edit');
    Route::patch('settings/account', [ProfileController::class, 'update'])->middleware('throttle:account-write')->name('account.update');

    Route::get('settings/account/confirm-email/{id}/{hash}', [ProfileController::class, 'confirmEmail'])
        ->whereNumber('id')
        ->middleware(['signed', 'throttle:account-write'])
        ->name('account.email.confirm');
    Route::delete('settings/account/pending-email', [ProfileController::class, 'cancelEmail'])->middleware('throttle:account-write')->name('account.email.cancel');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('settings/password', [PasswordController::class, 'update'])->middleware('throttle:account-write')->name('password.update');

    Route::get('settings/two-factor', [TwoFactorController::class, 'edit'])->name('two-factor.edit');
    Route::middleware('throttle:account-write')->group(function () {
        Route::post('settings/two-factor', [TwoFactorController::class, 'store'])->name('two-factor.store');
        Route::post('settings/two-factor/confirm', [TwoFactorController::class, 'confirm'])->name('two-factor.confirm');
        Route::post('settings/two-factor/recovery-codes', [TwoFactorController::class, 'recoveryCodes'])->name('two-factor.recovery-codes');
        Route::delete('settings/two-factor', [TwoFactorController::class, 'destroy'])->name('two-factor.destroy');
    });

    Route::get('settings/appearance', fn () => Inertia::render('settings/appearance'))->name('appearance');

    Route::get('settings/data', [DataController::class, 'edit'])->name('data.edit');
    Route::get('settings/data/export', [DataController::class, 'export'])->middleware('throttle:export')->name('data.export');
    Route::delete('settings/data', [DataController::class, 'destroy'])->middleware('throttle:account-write')->name('data.destroy');
});
