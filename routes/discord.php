<?php

use App\Http\Controllers\Discord\DiscordInstallController;
use App\Http\Controllers\Discord\DiscordServerController;
use App\Http\Controllers\Discord\WatchedPlayerController;
use Illuminate\Support\Facades\Route;

Route::prefix('dashboard')
    ->name('dashboard.')
    ->middleware(['auth', 'verified'])
    ->group(function () {
        Route::prefix('discord')->name('discord.')->group(function () {
            Route::get('/', [DiscordServerController::class, 'index'])->name('index');
            Route::get('create', [DiscordServerController::class, 'create'])->name('create');
            Route::get('install', [DiscordInstallController::class, 'redirect'])->name('install');
            Route::get('install/callback', [DiscordInstallController::class, 'callback'])->name('install.callback');

            Route::scopeBindings()->group(function () {
                Route::get('servers/{discordServer}', [DiscordServerController::class, 'show'])->name('servers.show');
                Route::get('servers/{discordServer}/edit', [DiscordServerController::class, 'edit'])->name('servers.edit');
                Route::patch('servers/{discordServer}', [DiscordServerController::class, 'update'])->name('servers.update');
                Route::delete('servers/{discordServer}', [DiscordServerController::class, 'destroy'])->name('servers.destroy');

                Route::get('servers/{discordServer}/watched-players/create', [WatchedPlayerController::class, 'create'])
                    ->name('servers.watched-players.create');
                Route::post('servers/{discordServer}/watched-players', [WatchedPlayerController::class, 'store'])
                    ->name('servers.watched-players.store');
                Route::get('servers/{discordServer}/watched-players/{watchedPlayer}/edit', [WatchedPlayerController::class, 'edit'])
                    ->name('servers.watched-players.edit');
                Route::patch('servers/{discordServer}/watched-players/{watchedPlayer}', [WatchedPlayerController::class, 'update'])
                    ->name('servers.watched-players.update');
                Route::post('servers/{discordServer}/watched-players/{watchedPlayer}/refresh', [WatchedPlayerController::class, 'refresh'])
                    ->name('servers.watched-players.refresh');
                Route::delete('servers/{discordServer}/watched-players/{watchedPlayer}', [WatchedPlayerController::class, 'destroy'])
                    ->name('servers.watched-players.destroy');
            });
        });
    });
