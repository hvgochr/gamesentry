<?php

use App\Models\MatchNotification;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('gamesentry:dispatch-watched-player-polls')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::call(fn () => Cache::put('gamesentry:scheduler:last-run-at', now()->toIso8601String(), now()->addMinutes(10)))
    ->name('gamesentry:scheduler-heartbeat')
    ->everyMinute();

Schedule::command('model:prune', [
    '--model' => [MatchNotification::class],
])
    ->daily()
    ->withoutOverlapping();
