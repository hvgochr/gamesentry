<?php

namespace Tests\Feature\Services;

use App\Enums\Game;
use App\Services\Riot\Exceptions\RiotRateLimitException;
use App\Services\Riot\RiotApiService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class RiotApiServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.riot.lol_key' => 'riot-lol-key',
            'services.riot.tft_key' => 'riot-tft-key',
        ]);

        Http::preventStrayRequests();
    }

    public function test_resolve_account_by_riot_id_returns_the_account_payload(): void
    {
        Http::fake([
            'https://europe.api.riotgames.com/riot/account/v1/accounts/by-riot-id/TestName/EUW1' => Http::response([
                'puuid' => 'riot-puuid-1',
                'gameName' => 'TestName',
                'tagLine' => 'EUW1',
            ], 200),
        ]);

        $account = app(RiotApiService::class)->resolveAccountByRiotId(
            Game::LeagueOfLegends,
            'europe',
            'TestName',
            'EUW1',
        );

        $this->assertSame('riot-puuid-1', $account['puuid']);

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://europe.api.riotgames.com/riot/account/v1/accounts/by-riot-id/TestName/EUW1'
                && $request->hasHeader('X-Riot-Token', 'riot-lol-key');
        });
    }

    public function test_resolve_account_by_riot_id_returns_null_when_the_account_does_not_exist(): void
    {
        Http::fake([
            'https://europe.api.riotgames.com/riot/account/v1/accounts/by-riot-id/TestName/EUW1' => Http::response([], 404),
        ]);

        $account = app(RiotApiService::class)->resolveAccountByRiotId(
            Game::LeagueOfLegends,
            'europe',
            'TestName',
            'EUW1',
        );

        $this->assertNull($account);
    }

    public function test_recent_match_ids_and_latest_match_id_return_the_expected_matches(): void
    {
        Http::fake([
            'https://europe.api.riotgames.com/lol/match/v5/matches/by-puuid/riot-puuid-1/ids*' => Http::response([
                'EUW1_300',
                'EUW1_299',
            ], 200),
        ]);

        $recentMatches = app(RiotApiService::class)->recentMatchIds(
            Game::LeagueOfLegends,
            'europe',
            'riot-puuid-1',
            2,
        );

        $latestMatchId = app(RiotApiService::class)->latestMatchId(
            Game::LeagueOfLegends,
            'europe',
            'riot-puuid-1',
        );

        $this->assertSame(['EUW1_300', 'EUW1_299'], $recentMatches);
        $this->assertSame('EUW1_300', $latestMatchId);
    }

    public function test_recent_match_ids_throw_a_rate_limit_exception_with_retry_after_information(): void
    {
        Http::fake([
            'https://europe.api.riotgames.com/lol/match/v5/matches/by-puuid/riot-puuid-2/ids*' => Http::response(
                [],
                429,
                ['Retry-After' => '90'],
            ),
        ]);

        try {
            app(RiotApiService::class)->recentMatchIds(
                Game::LeagueOfLegends,
                'europe',
                'riot-puuid-2',
            );
            $this->fail('Expected a RiotRateLimitException to be thrown.');
        } catch (RiotRateLimitException $exception) {
            $this->assertSame(90, $exception->retryAfterSeconds());
        }
    }

    public function test_match_throws_a_runtime_exception_when_the_match_cannot_be_found(): void
    {
        Http::fake([
            'https://europe.api.riotgames.com/lol/match/v5/matches/EUW1_404' => Http::response([], 404),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('This Riot match can\'t be found.');

        app(RiotApiService::class)->match(
            Game::LeagueOfLegends,
            'europe',
            'EUW1_404',
        );
    }
}
