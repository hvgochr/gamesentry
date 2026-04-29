<?php

namespace Tests\Feature;

use App\Enums\Game;
use App\Jobs\PollWatchedPlayerJob;
use App\Models\DiscordServer;
use App\Models\User;
use App\Models\WatchedPlayer;
use App\Services\Riot\RiotPollingService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DispatchWatchedPlayerPollsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_dispatches_due_players_with_per_game_budget_and_interval(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-04-14 17:10:00'));

        config()->set('gamesentry.polling.dispatch_budget_per_minute.lol', 2);
        config()->set('gamesentry.polling.dispatch_budget_per_minute.tft', 1);
        config()->set('gamesentry.polling.min_interval_seconds', 60);
        config()->set('gamesentry.polling.max_interval_seconds', 900);

        $discordServer = $this->createDiscordServer();
        $firstDue = $this->createWatchedPlayer($discordServer, [
            'game' => Game::LeagueOfLegends->value,
            'next_poll_at' => now()->subMinutes(3),
        ]);
        $secondDue = $this->createWatchedPlayer($discordServer, [
            'game' => Game::LeagueOfLegends->value,
            'next_poll_at' => now()->subMinutes(2),
        ]);
        $thirdDue = $this->createWatchedPlayer($discordServer, [
            'game' => Game::LeagueOfLegends->value,
            'next_poll_at' => now()->subMinute(),
        ]);
        $inactiveDue = $this->createWatchedPlayer($discordServer, [
            'game' => Game::LeagueOfLegends->value,
            'next_poll_at' => now()->subMinutes(4),
            'is_active' => false,
        ]);
        $futurePlayer = $this->createWatchedPlayer($discordServer, [
            'game' => Game::LeagueOfLegends->value,
            'next_poll_at' => now()->addMinute(),
        ]);
        $tftDue = $this->createWatchedPlayer($discordServer, [
            'game' => Game::TeamfightTactics->value,
            'next_poll_at' => now()->subMinutes(5),
        ]);

        Queue::fake();

        $this->artisan('gamesentry:dispatch-watched-player-polls')->assertSuccessful();

        Queue::assertPushed(PollWatchedPlayerJob::class, 3);
        Queue::assertPushed(PollWatchedPlayerJob::class, fn (PollWatchedPlayerJob $job) => $job->watchedPlayerId === $firstDue->id
            && $job->desiredPollIntervalSeconds === 120);
        Queue::assertPushed(PollWatchedPlayerJob::class, fn (PollWatchedPlayerJob $job) => $job->watchedPlayerId === $secondDue->id
            && $job->desiredPollIntervalSeconds === 120);
        Queue::assertPushed(PollWatchedPlayerJob::class, fn (PollWatchedPlayerJob $job) => $job->watchedPlayerId === $tftDue->id
            && $job->desiredPollIntervalSeconds === 60);
        Queue::assertNotPushed(PollWatchedPlayerJob::class, fn (PollWatchedPlayerJob $job) => in_array($job->watchedPlayerId, [
            $thirdDue->id,
            $inactiveDue->id,
            $futurePlayer->id,
        ], true));
    }

    public function test_command_delays_due_players_when_a_game_is_in_global_backoff(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-04-14 17:10:00'));

        $discordServer = $this->createDiscordServer();
        $lolPlayer = $this->createWatchedPlayer($discordServer, [
            'game' => Game::LeagueOfLegends->value,
            'next_poll_at' => now()->subMinute(),
        ]);
        $tftPlayer = $this->createWatchedPlayer($discordServer, [
            'game' => Game::TeamfightTactics->value,
            'next_poll_at' => now()->subMinute(),
        ]);

        $backoffUntil = app(RiotPollingService::class)->recordRateLimitHit(Game::LeagueOfLegends, 180);

        Queue::fake();

        $this->artisan('gamesentry:dispatch-watched-player-polls')->assertSuccessful();

        $lolPlayer->refresh();

        $this->assertSame($backoffUntil->toDateTimeString(), $lolPlayer->next_poll_at?->toDateTimeString());
        Queue::assertNotPushed(PollWatchedPlayerJob::class, fn (PollWatchedPlayerJob $job) => $job->watchedPlayerId === $lolPlayer->id);
        Queue::assertPushed(PollWatchedPlayerJob::class, fn (PollWatchedPlayerJob $job) => $job->watchedPlayerId === $tftPlayer->id);
    }

    public function test_poll_dispatch_command_is_scheduled_every_minute(): void
    {
        $schedule = $this->app->make(Schedule::class);
        $event = collect($schedule->events())->first(
            fn (object $event) => str_contains((string) $event->command, 'gamesentry:dispatch-watched-player-polls'),
        );

        $this->assertNotNull($event);
        $this->assertSame('* * * * *', $event->expression);
    }

    protected function tearDown(): void
    {
        Cache::flush();
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    private function createDiscordServer(): DiscordServer
    {
        $user = User::factory()->create();

        return DiscordServer::query()->create([
            'user_id' => $user->id,
            'discord_guild_id' => '123456789012345678',
            'name' => 'Gamesentry HQ',
            'icon' => null,
            'discord_channel_id' => '223456789012345678',
            'discord_channel_name' => 'roasts',
        ]);
    }

    private function createWatchedPlayer(DiscordServer $discordServer, array $overrides = []): WatchedPlayer
    {
        static $sequence = 0;

        $sequence++;

        return WatchedPlayer::query()->create(array_merge([
            'discord_server_id' => $discordServer->id,
            'game' => Game::LeagueOfLegends->value,
            'routing_region' => 'europe',
            'game_name' => "Player{$sequence}",
            'tag_line' => "TAG{$sequence}",
            'riot_puuid' => "puuid-{$sequence}",
            'discord_user_id' => "1000000000000000{$sequence}",
            'last_seen_match_id' => "MATCH-{$sequence}",
            'last_polled_at' => null,
            'next_poll_at' => now()->subMinute(),
            'poll_interval_seconds' => 300,
            'is_active' => true,
        ], $overrides));
    }
}
