<?php

namespace Tests\Unit\Services\Riot;

use App\Enums\Game;
use App\Services\Riot\RiotProfileService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RiotProfileServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.riot.lol_key', 'riot-lol-key');
        Http::preventStrayRequests();
    }

    public function test_it_uses_the_match_platform_before_regional_fallbacks(): void
    {
        Http::fake([
            'https://eun1.api.riotgames.com/lol/summoner/v4/summoners/by-puuid/player-puuid' => Http::response([
                'profileIconId' => 4567,
            ]),
        ]);

        $profileIconId = app(RiotProfileService::class)->profileIconId(
            Game::LeagueOfLegends,
            'europe',
            'player-puuid',
            'EUN1_123456789',
        );

        $this->assertSame(4567, $profileIconId);
        Http::assertSentCount(1);
    }

    public function test_it_falls_back_across_platforms_in_the_routing_region(): void
    {
        Http::fake([
            'https://euw1.api.riotgames.com/*' => Http::response(status: 404),
            'https://eun1.api.riotgames.com/lol/summoner/v4/summoners/by-puuid/player-puuid' => Http::response([
                'profileIconId' => 9876,
            ]),
        ]);

        $profileIconId = app(RiotProfileService::class)->profileIconId(
            Game::LeagueOfLegends,
            'europe',
            'player-puuid',
        );

        $this->assertSame(9876, $profileIconId);
        Http::assertSentCount(2);
    }

    public function test_it_does_not_use_summoner_v4_for_tft(): void
    {
        $profileIconId = app(RiotProfileService::class)->profileIconId(
            Game::TeamfightTactics,
            'europe',
            'player-puuid',
        );

        $this->assertNull($profileIconId);
        Http::assertNothingSent();
    }
}
