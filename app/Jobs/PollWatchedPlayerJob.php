<?php

namespace App\Jobs;

use App\Enums\MatchNotificationStatus;
use App\Models\MatchNotification;
use App\Models\WatchedPlayer;
use App\Services\Riot\Exceptions\RiotRateLimitException;
use App\Services\Riot\RiotApiService;
use App\Services\Riot\RiotPollingService;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Throwable;

class PollWatchedPlayerJob implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    /**
     * @var list<int>
     */
    public array $backoff = [5, 30, 120];

    public function __construct(
        public int $watchedPlayerId,
        public int $desiredPollIntervalSeconds,
    ) {}

    public function uniqueId(): string
    {
        return "watched-player:{$this->watchedPlayerId}";
    }

    /**
     * @return list<object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping("watched-player:{$this->watchedPlayerId}"))
                ->releaseAfter(5)
                ->expireAfter(180),
        ];
    }

    public function handle(RiotApiService $riot, RiotPollingService $polling): void
    {
        $watchedPlayer = WatchedPlayer::query()->find($this->watchedPlayerId);

        if ($watchedPlayer === null || ! $watchedPlayer->is_active) {
            return;
        }

        try {
            $recentMatchIds = $riot->recentMatchIds(
                $watchedPlayer->game,
                $watchedPlayer->routing_region,
                $watchedPlayer->riot_puuid,
                $polling->recentMatchHistoryCount(),
            );
        } catch (RiotRateLimitException $exception) {
            $backoffUntil = $polling->recordRateLimitHit(
                $watchedPlayer->game,
                $exception->retryAfterSeconds(),
            );

            $watchedPlayer->forceFill([
                'next_poll_at' => $backoffUntil,
            ])->save();

            throw $exception;
        }

        $polling->decayBackoff($watchedPlayer->game);

        $this->persistNewMatches($watchedPlayer, $recentMatchIds);

        $watchedPlayer->forceFill([
            'last_seen_match_id' => $recentMatchIds[0] ?? $watchedPlayer->last_seen_match_id,
            'last_polled_at' => now(),
            'next_poll_at' => now()->addSeconds($this->desiredPollIntervalSeconds),
            'poll_interval_seconds' => $this->desiredPollIntervalSeconds,
        ])->save();
    }

    public function failed(?Throwable $exception): void
    {
        if ($exception === null) {
            return;
        }

        Log::error('Watched player polling failed.', [
            'watched_player_id' => $this->watchedPlayerId,
            'error' => $exception->getMessage(),
        ]);

        $watchedPlayer = WatchedPlayer::query()->find($this->watchedPlayerId);

        if ($watchedPlayer === null) {
            return;
        }

        if ($watchedPlayer->next_poll_at !== null && $watchedPlayer->next_poll_at->isFuture()) {
            return;
        }

        $watchedPlayer->forceFill([
            'next_poll_at' => now()->addSeconds(
                max($watchedPlayer->poll_interval_seconds, $this->desiredPollIntervalSeconds),
            ),
        ])->save();
    }

    /**
     * @param  list<string>  $recentMatchIds
     */
    private function persistNewMatches(WatchedPlayer $watchedPlayer, array $recentMatchIds): void
    {
        foreach ($this->newMatchIds($recentMatchIds, $watchedPlayer->last_seen_match_id) as $matchId) {
            $notification = MatchNotification::query()->firstOrCreate(
                [
                    'watched_player_id' => $watchedPlayer->id,
                    'riot_match_id' => $matchId,
                ],
                [
                    'discord_server_id' => $watchedPlayer->discord_server_id,
                    'game' => $watchedPlayer->game,
                    'status' => MatchNotificationStatus::Pending,
                ],
            );

            if ($notification->wasRecentlyCreated) {
                ProcessMatchNotificationJob::dispatch($notification->id);
            }
        }
    }

    /**
     * @param  list<string>  $recentMatchIds
     * @return list<string>
     */
    private function newMatchIds(array $recentMatchIds, ?string $lastSeenMatchId): array
    {
        if ($lastSeenMatchId === null || $recentMatchIds === []) {
            return [];
        }

        $newMatchIds = [];

        foreach ($recentMatchIds as $matchId) {
            if ($matchId === $lastSeenMatchId) {
                break;
            }

            $newMatchIds[] = $matchId;
        }

        return array_reverse($newMatchIds);
    }
}
