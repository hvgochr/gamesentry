<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiscordServerController;
use App\Http\Controllers\TrackedPlayerController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('servers', [DiscordServerController::class, 'index'])->name('servers.index');
    Route::post('servers', [DiscordServerController::class, 'store'])->name('servers.store');
    Route::patch('servers/{discordServer}', [DiscordServerController::class, 'update'])->name('servers.update');
    Route::delete('servers/{discordServer}', [DiscordServerController::class, 'destroy'])->name('servers.destroy');

    Route::get('players', [TrackedPlayerController::class, 'index'])->name('players.index');
    Route::post('players', [TrackedPlayerController::class, 'store'])->name('players.store');
    Route::patch('players/{trackedPlayer}', [TrackedPlayerController::class, 'update'])->name('players.update');
    Route::delete('players/{trackedPlayer}', [TrackedPlayerController::class, 'destroy'])->name('players.destroy');
});

require __DIR__.'/settings.php';
