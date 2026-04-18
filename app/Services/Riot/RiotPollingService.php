<?php

namespace App\Services\Riot;

use App\Enums\Game;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

class RiotPollingService
{
    public function dispatchLimit(Game $game): int
    {
        return max((int) config("gamesentry.polling.dispatch_budget_per_minute.{$game->value}", 15), 1);
    }

    public function recentMatchHistoryCount(): int
    {
        return max((int) config('gamesentry.polling.match_history_count', 10), 1);
    }

    public function desiredPollInterval(Game $game, ?int $activePlayerCount = null): int
    {
        $activePlayerCount ??= 1;
        $minInterval = max((int) config('gamesentry.polling.min_interval_seconds', 300), 60);
        $maxInterval = max((int) config('gamesentry.polling.max_interval_seconds', 1800), $minInterval);
        $volumeInterval = (int) ceil(max($activePlayerCount, 1) / $this->dispatchLimit($game)) * 60;
        $multiplier = max((int) Cache::get($this->multiplierKey($game), 1), 1);

        return min(
            $maxInterval,
            max($minInterval, $volumeInterval * $multiplier),
        );
    }

    public function backoffUntil(Game $game): ?CarbonImmutable
    {
        $timestamp = Cache::get($this->backoffKey($game));

        if (! is_int($timestamp)) {
            return null;
        }

        return CarbonImmutable::createFromTimestamp($timestamp);
    }

    public function recordRateLimitHit(Game $game, ?int $retryAfterSeconds = null): CarbonImmutable
    {
        $multiplier = min(
            max((int) Cache::get($this->multiplierKey($game), 1), 1) + 1,
            max((int) config('gamesentry.polling.max_backoff_multiplier', 6), 2),
        );

        $cooldownSeconds = max(
            $retryAfterSeconds ?? 0,
            max((int) config('gamesentry.polling.rate_limit_cooldown_seconds', 120), 30) * $multiplier,
        );

        $until = CarbonImmutable::now()->addSeconds($cooldownSeconds);

        Cache::put($this->multiplierKey($game), $multiplier, now()->addDay());
        Cache::put($this->backoffKey($game), $until->getTimestamp(), $until);

        return $until;
    }

    public function decayBackoff(Game $game): void
    {
        $current = max((int) Cache::get($this->multiplierKey($game), 1), 1);
        $next = max($current - 1, 1);

        Cache::forget($this->backoffKey($game));
        Cache::put($this->multiplierKey($game), $next, now()->addDay());
    }

    private function backoffKey(Game $game): string
    {
        return "riot:polling:{$game->value}:backoff-until";
    }

    private function multiplierKey(Game $game): string
    {
        return "riot:polling:{$game->value}:multiplier";
    }
}
