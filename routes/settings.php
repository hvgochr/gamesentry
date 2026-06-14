<?php

use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Support\Facades\Route;

Route::prefix('dashboard')->name('dashboard.')->group(function () {
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::middleware(['auth'])->group(function () {
            Route::redirect('/', '/dashboard/settings/profile')->name('index');

            Route::prefix('profile')->name('profile.')->group(function () {
                Route::get('/', [ProfileController::class, 'edit'])->name('edit');
                Route::patch('/', [ProfileController::class, 'update'])->name('update');
            });
        });

        Route::middleware(['auth', 'verified'])->group(function () {
            Route::delete('profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

            Route::prefix('security')->name('security.')->group(function () {
                Route::get('/', [SecurityController::class, 'edit'])
                    ->middleware(RequirePassword::class)
                    ->name('edit');
            });

            Route::prefix('password')->name('password.')->group(function () {
                Route::put('/', [SecurityController::class, 'update'])
                    ->middleware('throttle:6,1')
                    ->name('update');
            });

            Route::prefix('appearance')->name('appearance.')->group(function () {
                Route::inertia('/', 'settings/appearance')->name('edit');
            });
        });
    });
});
