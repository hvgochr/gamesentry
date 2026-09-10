<?php

namespace App\Actions\Riot;

use App\Models\WatchedPlayer;
use App\Services\Riot\RiotApiService;
use App\Services\Riot\RiotProfileService;
use RuntimeException;

class RefreshWatchedPlayerProfile
{
    public function __construct(
        private readonly RiotApiService $riot,
        private readonly RiotProfileService $profiles,
    ) {}

    public function handle(WatchedPlayer $watchedPlayer): void
    {
        $account = $this->riot->accountByPuuid(
            $watchedPlayer->game,
            $watchedPlayer->routing_region,
            $watchedPlayer->riot_puuid,
        );

        if ($account === null) {
            throw new RuntimeException('There is no Riot account associated with this player.');
        }

        $watchedPlayer->forceFill([
            'game_name' => $account['gameName'] ?? $watchedPlayer->game_name,
            'tag_line' => $account['tagLine'] ?? $watchedPlayer->tag_line,
            'profile_icon_id' => $this->profiles->profileIconId(
                $watchedPlayer->game,
                $watchedPlayer->routing_region,
                $watchedPlayer->riot_puuid,
                $watchedPlayer->last_seen_match_id,
            ),
            'profile_refreshed_at' => now(),
        ])->save();
    }
}
