<?php

namespace Tests\Unit\Services;

use App\Models\DailyUsageCounter;
use App\Models\DiscordServer;
use App\Models\User;
use App\Models\WatchedPlayer;
use App\Services\Plans\Exceptions\PlanLimitExceededException;
use App\Services\Plans\PlanLimitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanLimitServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_free_users_are_limited_to_one_discord_server(): void
    {
        $user = User::factory()->create();
        DiscordServer::factory()->for($user)->create();

        $this->expectException(PlanLimitExceededException::class);
        $this->expectExceptionMessage('free plan allows up to 1 linked Discord server.');

        app(PlanLimitService::class)->ensureCanCreateDiscordServer($user);
    }

    public function test_pro_users_are_limited_to_three_discord_servers(): void
    {
        $user = User::factory()->pro()->create();

        DiscordServer::factory()
            ->count(2)
            ->for($user)
            ->create();

        app(PlanLimitService::class)->ensureCanCreateDiscordServer($user);

        DiscordServer::factory()->for($user)->create();

        $this->expectException(PlanLimitExceededException::class);
        $this->expectExceptionMessage('pro plan allows up to 3 linked Discord servers.');

        app(PlanLimitService::class)->ensureCanCreateDiscordServer($user);
    }

    public function test_admin_users_have_unlimited_discord_servers_and_watched_players(): void
    {
        $user = User::factory()->admin()->create();

        DiscordServer::factory()
            ->count(5)
            ->for($user)
            ->has(WatchedPlayer::factory()->count(4))
            ->create();

        $limits = app(PlanLimitService::class);

        $this->assertNull($limits->discordServerLimit($user));
        $this->assertNull($limits->watchedPlayerLimit($user));

        $limits->ensureCanCreateDiscordServer($user);
        $limits->ensureCanCreateWatchedPlayer($user);
    }

    public function test_free_users_are_limited_to_three_watched_players(): void
    {
        $user = User::factory()->create();
        $server = DiscordServer::factory()->for($user)->create();

        WatchedPlayer::factory()
            ->count(3)
            ->for($server)
            ->create();

        $this->expectException(PlanLimitExceededException::class);
        $this->expectExceptionMessage('free plan allows up to 3 tracked players.');

        app(PlanLimitService::class)->ensureCanCreateWatchedPlayer($user);
    }

    public function test_groq_calls_are_counted_daily_until_the_plan_limit(): void
    {
        $user = User::factory()->create();

        DailyUsageCounter::factory()->for($user)->create([
            'usage_date' => today(),
            'groq_calls' => 49,
        ]);

        $limits = app(PlanLimitService::class);

        $limits->consumeGroqCall($user);

        $this->assertSame(50, $limits->groqCallsUsedToday($user));

        $this->expectException(PlanLimitExceededException::class);
        $this->expectExceptionMessage('free plan allows up to 50 Groq calls per day.');

        $limits->consumeGroqCall($user);
    }

    public function test_admin_groq_calls_are_not_counted(): void
    {
        $user = User::factory()->admin()->create();

        app(PlanLimitService::class)->consumeGroqCall($user);

        $this->assertDatabaseMissing('daily_usage_counters', [
            'user_id' => $user->id,
        ]);
    }
}
