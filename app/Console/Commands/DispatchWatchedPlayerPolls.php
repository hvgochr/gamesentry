<?php

namespace App\Console\Commands;

use App\Enums\Game;
use App\Jobs\PollWatchedPlayerJob;
use App\Models\WatchedPlayer;
use App\Services\Riot\RiotPollingService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

#[Signature('gamesentry:dispatch-watched-player-polls')]
#[Description('Dispatch polling jobs for watched players that are due to be checked for new matches.')]
class DispatchWatchedPlayerPolls extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(RiotPollingService $polling): int
    {
        $dispatchedJobs = 0;
        $delayedPlayers = 0;

        foreach (Game::cases() as $game) {
            $activePlayerCount = $this->activePlayerCountForGame($game);

            if ($activePlayerCount === 0) {
                continue;
            }

            $backoffUntil = $polling->backoffUntil($game);

            if ($backoffUntil !== null && $backoffUntil->isFuture()) {
                $delayedPlayers += $this->delayDuePlayersUntil($game, $backoffUntil);

                continue;
            }

            $desiredPollInterval = $polling->desiredPollInterval($game, $activePlayerCount);
            $duePlayerIds = $this->duePlayerIdsForGame($game, $polling->dispatchLimit($game));

            foreach ($duePlayerIds as $watchedPlayerId) {
                PollWatchedPlayerJob::dispatch($watchedPlayerId, $desiredPollInterval);
            }

            $dispatchedJobs += $duePlayerIds->count();
        }

        $this->info("Dispatched {$dispatchedJobs} watched player polling job(s).");

        if ($delayedPlayers > 0) {
            $this->warn("Delayed {$delayedPlayers} due watched player(s) because Riot polling is in backoff.");
        }

        return self::SUCCESS;
    }

    private function activePlayerCountForGame(Game $game): int
    {
        return WatchedPlayer::query()
            ->active()
            ->where('game', $game->value)
            ->count();
    }

    /**
     * @return Collection<int, int>
     */
    private function duePlayerIdsForGame(Game $game, int $limit): Collection
    {
        return WatchedPlayer::query()
            ->dueForPolling()
            ->where('game', $game->value)
            ->orderBy('next_poll_at')
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id');
    }

    private function delayDuePlayersUntil(Game $game, CarbonImmutable $backoffUntil): int
    {
        return WatchedPlayer::query()
            ->dueForPolling()
            ->where('game', $game->value)
            ->update([
                'next_poll_at' => $backoffUntil,
            ]);
    }
}
