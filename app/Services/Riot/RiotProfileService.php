<?php

namespace App\Services\Riot;

use App\Enums\Game;

class RiotProfileService
{
    /** @var array<string, list<string>> */
    private const array PlatformsByRegion = [
        'americas' => ['na1', 'br1', 'la1', 'la2'],
        'asia' => ['kr', 'jp1'],
        'europe' => ['euw1', 'eun1', 'tr1', 'ru'],
        'sea' => ['oc1', 'ph2', 'sg2', 'th2', 'tw2', 'vn2'],
    ];

    public function __construct(private readonly RiotApiService $riot) {}

    public function profileIconId(
        Game $game,
        string $routingRegion,
        string $puuid,
        ?string $knownMatchId = null,
    ): ?int {
        if ($game !== Game::LeagueOfLegends) {
            return null;
        }

        foreach ($this->platformCandidates($routingRegion, $knownMatchId) as $platformRegion) {
            $summoner = $this->riot->summonerByPuuid($platformRegion, $puuid);

            if ($summoner !== null) {
                return $summoner['profileIconId'];
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function platformCandidates(string $routingRegion, ?string $knownMatchId): array
    {
        $platforms = self::PlatformsByRegion[strtolower($routingRegion)] ?? [];

        if ($knownMatchId === null) {
            return $platforms;
        }

        $platform = strtolower(explode('_', $knownMatchId, 2)[0]);

        if (! in_array($platform, $platforms, true)) {
            return $platforms;
        }

        return array_values(array_unique([$platform, ...$platforms]));
    }
}
