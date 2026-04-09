<?php

use App\Http\Controllers\Discord\DiscordInstallController;
use App\Http\Controllers\Discord\DiscordServerController;
use App\Http\Controllers\Discord\WatchedPlayerController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('discord', [DiscordServerController::class, 'index'])->name('discord.index');
    Route::get('discord/install', [DiscordInstallController::class, 'redirect'])->name('discord.install');
    Route::get('discord/install/callback', [DiscordInstallController::class, 'callback'])->name('discord.install.callback');

    Route::scopeBindings()->group(function () {
        Route::get('discord/servers/{discordServer}', [DiscordServerController::class, 'show'])->name('discord.servers.show');
        Route::patch('discord/servers/{discordServer}', [DiscordServerController::class, 'update'])->name('discord.servers.update');
        Route::delete('discord/servers/{discordServer}', [DiscordServerController::class, 'destroy'])->name('discord.servers.destroy');

        Route::post('discord/servers/{discordServer}/watched-players', [WatchedPlayerController::class, 'store'])
            ->name('discord.servers.watched-players.store');
        Route::patch('discord/servers/{discordServer}/watched-players/{watchedPlayer}', [WatchedPlayerController::class, 'update'])
            ->name('discord.servers.watched-players.update');
        Route::delete('discord/servers/{discordServer}/watched-players/{watchedPlayer}', [WatchedPlayerController::class, 'destroy'])
            ->name('discord.servers.watched-players.destroy');
    });
});
