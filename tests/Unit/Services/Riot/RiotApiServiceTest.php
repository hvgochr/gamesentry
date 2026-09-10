<?php

namespace Tests\Unit\Services\Riot;

use App\Services\Riot\Exceptions\RiotRateLimitException;
use App\Services\Riot\RiotApiService;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class RiotApiServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.riot.lol_key', 'riot-lol-key');
        Http::preventStrayRequests();
    }

    public function test_it_fetches_a_league_summoner_by_puuid(): void
    {
        Http::fake([
            'https://euw1.api.riotgames.com/lol/summoner/v4/summoners/by-puuid/player-puuid' => Http::response([
                'id' => 'summoner-id',
                'accountId' => 'account-id',
                'puuid' => 'player-puuid',
                'profileIconId' => 4567,
                'revisionDate' => 1_700_000_000_000,
                'summonerLevel' => 123,
            ]),
        ]);

        $summoner = app(RiotApiService::class)->summonerByPuuid('euw1', 'player-puuid');

        $this->assertNotNull($summoner);
        $this->assertSame(4567, $summoner['profileIconId']);
        Http::assertSentCount(1);
    }

    public function test_it_returns_null_when_the_summoner_is_not_found(): void
    {
        Http::fake([
            'https://euw1.api.riotgames.com/lol/summoner/v4/summoners/by-puuid/missing-puuid' => Http::response(status: 404),
        ]);

        $this->assertNull(
            app(RiotApiService::class)->summonerByPuuid('euw1', 'missing-puuid'),
        );
    }

    public function test_it_throws_a_clear_error_for_a_failed_summoner_lookup(): void
    {
        Http::fake([
            'https://euw1.api.riotgames.com/lol/summoner/v4/summoners/by-puuid/player-puuid' => Http::response(status: 500),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to retrieve this League of Legends profile.');

        app(RiotApiService::class)->summonerByPuuid('euw1', 'player-puuid');
    }

    public function test_summoner_lookup_preserves_riot_rate_limit_handling(): void
    {
        Http::fake([
            'https://euw1.api.riotgames.com/lol/summoner/v4/summoners/by-puuid/player-puuid' => Http::response(
                status: 429,
                headers: ['Retry-After' => '37'],
            ),
        ]);

        try {
            app(RiotApiService::class)->summonerByPuuid('euw1', 'player-puuid');
            $this->fail('Expected a Riot rate limit exception.');
        } catch (RiotRateLimitException $exception) {
            $this->assertSame(37, $exception->retryAfterSeconds());
        }
    }
}
