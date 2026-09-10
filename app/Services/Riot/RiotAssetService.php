<?php

namespace App\Services\Riot;

use App\Enums\Game;
use RuntimeException;

class RiotAssetService
{
    public function __construct(private readonly DataDragonService $dataDragon) {}

    public function profileIconUrl(?int $profileIconId): ?string
    {
        if ($profileIconId === null) {
            return null;
        }

        try {
            return $this->dataDragon->profileIconUrl($profileIconId);
        } catch (RuntimeException) {
            return null;
        }
    }

    public function championIconUrl(Game $game, mixed $champion): ?string
    {
        if ($game !== Game::LeagueOfLegends || ! is_string($champion) || $champion === '') {
            return null;
        }

        try {
            return $this->dataDragon->championImageUrl($champion);
        } catch (RuntimeException) {
            return null;
        }
    }
}
