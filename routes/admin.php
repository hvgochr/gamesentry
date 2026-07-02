<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminDiscordServerController;
use App\Http\Controllers\Admin\AdminFailedJobController;
use App\Http\Controllers\Admin\AdminNotificationController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminWatchedPlayerController;
use Illuminate\Support\Facades\Route;

Route::prefix('dashboard/admin')
    ->name('dashboard.admin.')
    ->middleware(['auth', 'verified', 'admin'])
    ->group(function () {
        Route::get('/', AdminDashboardController::class)->name('index');
        Route::post('users/{user}/pause', [AdminUserController::class, 'pause'])->name('users.pause');
        Route::post('users/{user}/resume', [AdminUserController::class, 'resume'])->name('users.resume');
        Route::post('notifications/{notification}/retry', [AdminNotificationController::class, 'retry'])->name('notifications.retry');
        Route::post('failed-jobs/{uuid}/retry', [AdminFailedJobController::class, 'retry'])->name('failed-jobs.retry');
        Route::patch('watched-players/{watchedPlayer}/disable', [AdminWatchedPlayerController::class, 'disable'])->name('watched-players.disable');
        Route::delete('discord-servers/{discordServer}', [AdminDiscordServerController::class, 'unlink'])->name('discord-servers.unlink');
    });
