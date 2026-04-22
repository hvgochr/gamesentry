<?php

namespace Tests\Unit\Services;

use App\Enums\Game;
use App\Services\Riot\RiotPollingService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class RiotPollingServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-04-20 20:00:00');
        CarbonImmutable::setTestNow('2026-04-20 20:00:00');

        config([
            'cache.default' => 'array',
            'gamesentry.polling.dispatch_budget_per_minute.lol' => 15,
            'gamesentry.polling.dispatch_budget_per_minute.tft' => 15,
            'gamesentry.polling.match_history_count' => 10,
            'gamesentry.polling.min_interval_seconds' => 300,
            'gamesentry.polling.max_interval_seconds' => 1800,
            'gamesentry.polling.rate_limit_cooldown_seconds' => 120,
            'gamesentry.polling.max_backoff_multiplier' => 6,
        ]);

        Cache::flush();
    }

    public function test_dispatch_limits_and_history_count_fall_back_to_a_safe_minimum(): void
    {
        config([
            'gamesentry.polling.dispatch_budget_per_minute.lol' => 0,
            'gamesentry.polling.dispatch_budget_per_minute.tft' => 22,
            'gamesentry.polling.match_history_count' => 0,
        ]);

        $service = new RiotPollingService;

        $this->assertSame(1, $service->dispatchLimit(Game::LeagueOfLegends));
        $this->assertSame(22, $service->dispatchLimit(Game::TeamfightTactics));
        $this->assertSame(1, $service->recentMatchHistoryCount());
    }

    public function test_desired_poll_interval_respects_volume_multiplier_and_maximum_cap(): void
    {
        config([
            'gamesentry.polling.dispatch_budget_per_minute.lol' => 10,
            'gamesentry.polling.min_interval_seconds' => 60,
            'gamesentry.polling.max_interval_seconds' => 600,
        ]);

        Cache::put('riot:polling:lol:multiplier', 3, now()->addDay());

        $service = new RiotPollingService;

        $this->assertSame(180, $service->desiredPollInterval(Game::LeagueOfLegends, 1));
        $this->assertSame(600, $service->desiredPollInterval(Game::LeagueOfLegends, 40));
    }

    public function test_record_rate_limit_hit_persists_backoff_until_and_increases_the_multiplier(): void
    {
        config([
            'gamesentry.polling.dispatch_budget_per_minute.lol' => 10,
            'gamesentry.polling.min_interval_seconds' => 60,
            'gamesentry.polling.max_interval_seconds' => 600,
            'gamesentry.polling.rate_limit_cooldown_seconds' => 120,
        ]);

        $service = new RiotPollingService;

        $backoffUntil = $service->recordRateLimitHit(Game::LeagueOfLegends, 90);

        $this->assertSame(
            CarbonImmutable::parse('2026-04-20 20:04:00')->toIso8601String(),
            $backoffUntil->toIso8601String(),
        );
        $this->assertSame($backoffUntil->toIso8601String(), $service->backoffUntil(Game::LeagueOfLegends)?->toIso8601String());
        $this->assertSame(120, $service->desiredPollInterval(Game::LeagueOfLegends, 10));
    }

    public function test_record_rate_limit_hit_respects_the_configured_max_backoff_multiplier(): void
    {
        config([
            'gamesentry.polling.dispatch_budget_per_minute.lol' => 10,
            'gamesentry.polling.min_interval_seconds' => 60,
            'gamesentry.polling.max_interval_seconds' => 600,
            'gamesentry.polling.rate_limit_cooldown_seconds' => 30,
            'gamesentry.polling.max_backoff_multiplier' => 3,
        ]);

        $service = new RiotPollingService;

        $service->recordRateLimitHit(Game::LeagueOfLegends);
        $service->recordRateLimitHit(Game::LeagueOfLegends);
        $backoffUntil = $service->recordRateLimitHit(Game::LeagueOfLegends);

        $this->assertSame(
            CarbonImmutable::parse('2026-04-20 20:01:30')->toIso8601String(),
            $backoffUntil->toIso8601String(),
        );
        $this->assertSame(180, $service->desiredPollInterval(Game::LeagueOfLegends, 10));
    }

    public function test_decay_backoff_clears_the_backoff_key_and_reduces_the_multiplier_without_going_below_one(): void
    {
        config([
            'gamesentry.polling.dispatch_budget_per_minute.lol' => 10,
            'gamesentry.polling.min_interval_seconds' => 60,
            'gamesentry.polling.max_interval_seconds' => 600,
        ]);

        $service = new RiotPollingService;

        $service->recordRateLimitHit(Game::LeagueOfLegends);
        $service->recordRateLimitHit(Game::LeagueOfLegends);

        $service->decayBackoff(Game::LeagueOfLegends);

        $this->assertNull($service->backoffUntil(Game::LeagueOfLegends));
        $this->assertSame(120, $service->desiredPollInterval(Game::LeagueOfLegends, 10));

        $service->decayBackoff(Game::LeagueOfLegends);
        $service->decayBackoff(Game::LeagueOfLegends);

        $this->assertSame(60, $service->desiredPollInterval(Game::LeagueOfLegends, 10));
    }

    protected function tearDown(): void
    {
        Cache::flush();
        Carbon::setTestNow();
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }
}
