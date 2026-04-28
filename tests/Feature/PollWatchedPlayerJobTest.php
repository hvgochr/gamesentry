<?php

namespace Tests\Feature;

use App\Enums\MatchNotificationStatus;
use App\Jobs\PollWatchedPlayerJob;
use App\Jobs\ProcessMatchNotificationJob;
use App\Models\MatchNotification;
use App\Models\WatchedPlayer;
use App\Services\Riot\Exceptions\RiotRateLimitException;
use App\Services\Riot\RiotApiService;
use App\Services\Riot\RiotPollingService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;

class PollWatchedPlayerJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.riot.lol_key' => 'riot-lol-key',
            'services.riot.tft_key' => 'riot-tft-key',
        ]);

        Http::preventStrayRequests();
    }

    public function test_job_creates_notifications_for_new_matches_and_dispatches_processing_jobs(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-04-20 20:00:00'));

        $watchedPlayer = WatchedPlayer::factory()->leagueOfLegends()->create([
            'routing_region' => 'europe',
            'riot_puuid' => 'riot-puuid-1',
            'last_seen_match_id' => 'EUW1_100',
            'poll_interval_seconds' => 300,
            'next_poll_at' => now()->subMinute(),
        ]);

        Queue::fake();
        Http::fake([
            'https://europe.api.riotgames.com/lol/match/v5/matches/by-puuid/riot-puuid-1/ids*' => Http::response([
                'EUW1_103',
                'EUW1_102',
                'EUW1_101',
                'EUW1_100',
            ], 200),
        ]);

        $this->runJob($watchedPlayer, 600);

        $watchedPlayer->refresh();

        $this->assertSame([
            'EUW1_101',
            'EUW1_102',
            'EUW1_103',
        ], MatchNotification::query()
            ->where('watched_player_id', $watchedPlayer->id)
            ->orderBy('id')
            ->pluck('riot_match_id')
            ->all());
        $this->assertSame(3, MatchNotification::query()
            ->where('watched_player_id', $watchedPlayer->id)
            ->where('status', MatchNotificationStatus::Pending->value)
            ->count());
        $this->assertSame('EUW1_103', $watchedPlayer->last_seen_match_id);
        $this->assertSame(600, $watchedPlayer->poll_interval_seconds);
        $this->assertNotNull($watchedPlayer->last_polled_at);
        $this->assertSame(
            CarbonImmutable::now()->addSeconds(600)->toIso8601String(),
            $watchedPlayer->next_poll_at?->toIso8601String(),
        );

        Queue::assertPushed(ProcessMatchNotificationJob::class, 3);
    }

    public function test_job_does_not_create_notifications_when_the_player_has_never_been_polled_before(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-04-20 20:00:00'));

        $watchedPlayer = WatchedPlayer::factory()->leagueOfLegends()->neverPolled()->create([
            'routing_region' => 'europe',
            'riot_puuid' => 'riot-puuid-2',
            'last_seen_match_id' => null,
        ]);

        Queue::fake();
        Http::fake([
            'https://europe.api.riotgames.com/lol/match/v5/matches/by-puuid/riot-puuid-2/ids*' => Http::response([
                'EUW1_201',
                'EUW1_200',
            ], 200),
        ]);

        $this->runJob($watchedPlayer, 900);

        $watchedPlayer->refresh();

        $this->assertSame('EUW1_201', $watchedPlayer->last_seen_match_id);
        $this->assertSame(0, MatchNotification::query()->where('watched_player_id', $watchedPlayer->id)->count());
        Queue::assertNothingPushed();
    }

    public function test_job_returns_early_for_inactive_players(): void
    {
        $watchedPlayer = WatchedPlayer::factory()->inactive()->create([
            'next_poll_at' => now()->subMinute(),
        ]);

        Queue::fake();

        $this->runJob($watchedPlayer, 600);

        $this->assertSame(0, MatchNotification::query()->where('watched_player_id', $watchedPlayer->id)->count());
        Queue::assertNothingPushed();
    }

    public function test_job_records_backoff_and_rethrows_when_riot_rate_limits_polling(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-04-20 20:00:00'));

        $watchedPlayer = WatchedPlayer::factory()->leagueOfLegends()->create([
            'routing_region' => 'europe',
            'riot_puuid' => 'riot-puuid-3',
            'next_poll_at' => now()->subMinute(),
        ]);
        $originalLastPolledAt = $watchedPlayer->last_polled_at?->toIso8601String();

        Http::fake([
            'https://europe.api.riotgames.com/lol/match/v5/matches/by-puuid/riot-puuid-3/ids*' => Http::response(
                [],
                429,
                ['Retry-After' => '120'],
            ),
        ]);

        try {
            $this->runJob($watchedPlayer, 600);
            $this->fail('Expected a RiotRateLimitException to be thrown.');
        } catch (RiotRateLimitException) {
            $watchedPlayer->refresh();

            $this->assertSame(
                app(RiotPollingService::class)->backoffUntil($watchedPlayer->game)?->toIso8601String(),
                $watchedPlayer->next_poll_at?->toIso8601String(),
            );
            $this->assertSame($originalLastPolledAt, $watchedPlayer->last_polled_at?->toIso8601String());
        }
    }

    public function test_failed_reschedules_the_next_poll_when_no_future_backoff_exists(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-04-20 20:00:00'));

        $watchedPlayer = WatchedPlayer::factory()->create([
            'poll_interval_seconds' => 900,
            'next_poll_at' => now()->subMinute(),
        ]);

        $job = new PollWatchedPlayerJob($watchedPlayer->id, 600);

        $job->failed(new RuntimeException('Polling exploded.'));

        $watchedPlayer->refresh();

        $this->assertSame(
            CarbonImmutable::now()->addSeconds(900)->toIso8601String(),
            $watchedPlayer->next_poll_at?->toIso8601String(),
        );
    }

    public function test_failed_keeps_an_existing_future_next_poll_at(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-04-20 20:00:00'));

        $watchedPlayer = WatchedPlayer::factory()->create([
            'next_poll_at' => now()->addHour(),
        ]);
        $originalNextPollAt = $watchedPlayer->next_poll_at?->toIso8601String();

        $job = new PollWatchedPlayerJob($watchedPlayer->id, 600);

        $job->failed(new RuntimeException('Polling exploded.'));

        $this->assertSame($originalNextPollAt, $watchedPlayer->fresh()?->next_poll_at?->toIso8601String());
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    private function runJob(WatchedPlayer $watchedPlayer, int $desiredPollIntervalSeconds): void
    {
        $job = new PollWatchedPlayerJob($watchedPlayer->id, $desiredPollIntervalSeconds);

        $job->handle(
            app(RiotApiService::class),
            app(RiotPollingService::class),
        );
    }
}
