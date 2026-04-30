<?php

namespace Tests\Feature;

use App\Enums\Game;
use App\Enums\MatchNotificationStatus;
use App\Jobs\ProcessMatchNotificationJob;
use App\Models\DiscordServer;
use App\Models\MatchNotification;
use App\Models\User;
use App\Models\WatchedPlayer;
use App\Services\Discord\DiscordService;
use App\Services\Groq\GroqService;
use App\Services\Riot\Exceptions\RiotRateLimitException;
use App\Services\Riot\MatchSummaryService;
use App\Services\Riot\RiotApiService;
use App\Services\Riot\RiotPollingService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class ProcessMatchNotificationJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_sends_match_notification_with_a_persisted_delivery_nonce(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-04-16 21:15:00'));
        $this->configureExternalServices();

        $notification = $this->createMatchNotification();

        Http::preventStrayRequests();
        Http::fake([
            'https://europe.api.riotgames.com/lol/match/v5/matches/*' => Http::response(
                $this->leagueMatchPayload($notification->riot_match_id, $notification->watchedPlayer->riot_puuid),
                200,
            ),
            'https://api.groq.com/openai/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'A playful roast from Groq.']],
                ],
            ], 200),
            'https://discord.com/api/v10/channels/*/messages' => Http::response([
                'id' => '998877665544332211',
            ], 200),
        ]);

        $this->runJob($notification);

        $notification->refresh();

        $this->assertSame(MatchNotificationStatus::Sent, $notification->status);
        $this->assertSame('998877665544332211', $notification->discord_message_id);
        $this->assertNotNull($notification->discord_delivery_nonce);
        $this->assertNotNull($notification->delivered_at);
        $this->assertSame('A playful roast from Groq.', $notification->roast_text);

        Http::assertSent(function (Request $request) use ($notification): bool {
            $payload = $request->data();

            return $request->url() === 'https://discord.com/api/v10/channels/223456789012345678/messages'
                && ($payload['nonce'] ?? null) === $notification->discord_delivery_nonce
                && ($payload['enforce_nonce'] ?? null) === true;
        });
    }

    public function test_job_marks_notification_as_sent_when_discord_rejects_a_duplicate_nonce(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-04-16 21:15:00'));
        config()->set('services.discord.client_id', 'discord-client');
        config()->set('services.discord.redirect', 'https://gamesentry.test/discord/install/callback');
        config()->set('services.discord.bot_token', 'discord-bot-token');

        $notification = $this->createMatchNotification([
            'match_payload' => $this->preparedMatchPayload(),
            'discord_embed_payload' => $this->preparedDiscordEmbed(),
            'roast_text' => 'Already generated roast.',
            'discord_delivery_nonce' => 'delivery-nonce-1',
        ]);

        Http::preventStrayRequests();
        Http::fake([
            'https://discord.com/api/v10/channels/*/messages' => Http::response([
                'code' => 50035,
                'errors' => [
                    'nonce' => [
                        '_errors' => [
                            [
                                'code' => 'ENFORCE_UNIQUE',
                                'message' => 'nonce must be unique',
                            ],
                        ],
                    ],
                ],
                'message' => 'Invalid Form Body',
            ], 400),
        ]);

        $this->runJob($notification);

        $notification->refresh();

        $this->assertSame(MatchNotificationStatus::Sent, $notification->status);
        $this->assertSame('delivery-nonce-1', $notification->discord_delivery_nonce);
        $this->assertSame('Already generated roast.', $notification->roast_text);
        $this->assertNotNull($notification->delivered_at);
        $this->assertNull($notification->failure_reason);

        Http::assertSentCount(1);
    }

    public function test_job_records_polling_backoff_when_match_lookup_is_rate_limited(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-04-16 21:15:00'));
        $this->configureExternalServices();

        $notification = $this->createMatchNotification();

        Http::preventStrayRequests();
        Http::fake([
            'https://europe.api.riotgames.com/lol/match/v5/matches/*' => Http::response([], 429, [
                'Retry-After' => '120',
            ]),
        ]);

        try {
            $this->runJob($notification);
            $this->fail('Expected a RiotRateLimitException to be thrown.');
        } catch (RiotRateLimitException) {
            $notification->refresh();

            $this->assertSame(MatchNotificationStatus::Pending, $notification->status);
            $this->assertNull($notification->failure_reason);
            $this->assertNotNull(app(RiotPollingService::class)->backoffUntil(Game::LeagueOfLegends));
        }
    }

    public function test_failed_marks_unsent_notifications_as_failed(): void
    {
        $notification = $this->createMatchNotification();

        (new ProcessMatchNotificationJob($notification->id))
            ->failed(new RuntimeException('Discord delivery exploded.'));

        $notification->refresh();

        $this->assertSame(MatchNotificationStatus::Failed, $notification->status);
        $this->assertSame('Discord delivery exploded.', $notification->failure_reason);
    }

    public function test_failed_does_not_overwrite_a_notification_that_is_already_sent(): void
    {
        $notification = $this->createMatchNotification([
            'status' => MatchNotificationStatus::Sent,
            'failure_reason' => null,
            'delivered_at' => now(),
        ]);

        (new ProcessMatchNotificationJob($notification->id))
            ->failed(new RuntimeException('Discord delivery exploded.'));

        $notification->refresh();

        $this->assertSame(MatchNotificationStatus::Sent, $notification->status);
        $this->assertNull($notification->failure_reason);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    private function runJob(MatchNotification $notification): void
    {
        $job = new ProcessMatchNotificationJob($notification->id);

        $job->handle(
            app(RiotApiService::class),
            app(RiotPollingService::class),
            app(MatchSummaryService::class),
            app(GroqService::class),
            app(DiscordService::class),
        );
    }

    private function configureExternalServices(): void
    {
        config()->set('services.riot.lol_key', 'riot-lol-key');
        config()->set('services.groq.api_key', 'groq-key');
        config()->set('services.groq.model', 'llama-3.1-8b-instant');
        config()->set('services.groq.base_url', 'https://api.groq.com/openai/v1');
        config()->set('services.discord.client_id', 'discord-client');
        config()->set('services.discord.redirect', 'https://gamesentry.test/discord/install/callback');
        config()->set('services.discord.bot_token', 'discord-bot-token');
    }

    private function createMatchNotification(array $overrides = []): MatchNotification
    {
        $user = User::factory()->create();
        $discordServer = DiscordServer::query()->create([
            'user_id' => $user->id,
            'discord_guild_id' => '123456789012345678',
            'name' => 'Gamesentry HQ',
            'icon' => null,
            'discord_channel_id' => '223456789012345678',
            'discord_channel_name' => 'roasts',
        ]);
        $watchedPlayer = WatchedPlayer::query()->create([
            'discord_server_id' => $discordServer->id,
            'game' => Game::LeagueOfLegends->value,
            'routing_region' => 'europe',
            'game_name' => 'TrackedPlayer',
            'tag_line' => 'EUW',
            'riot_puuid' => 'puuid-1',
            'discord_user_id' => '100000000000000001',
            'last_seen_match_id' => 'MATCH-BASE',
            'next_poll_at' => now()->addMinute(),
            'poll_interval_seconds' => 300,
            'is_active' => true,
        ]);

        return MatchNotification::query()->create(array_merge([
            'watched_player_id' => $watchedPlayer->id,
            'discord_server_id' => $discordServer->id,
            'game' => Game::LeagueOfLegends->value,
            'riot_match_id' => 'EUW1_MATCH_123',
            'status' => MatchNotificationStatus::Pending,
        ], $overrides));
    }

    /**
     * @return array<string, mixed>
     */
    private function leagueMatchPayload(string $matchId, string $puuid): array
    {
        return [
            'metadata' => [
                'matchId' => $matchId,
            ],
            'info' => [
                'gameDuration' => 1800,
                'gameEndTimestamp' => CarbonImmutable::now()->getTimestampMs(),
                'gameMode' => 'CLASSIC',
                'participants' => [
                    [
                        'puuid' => $puuid,
                        'kills' => 10,
                        'deaths' => 2,
                        'assists' => 8,
                        'win' => true,
                        'championName' => 'Ahri',
                        'totalMinionsKilled' => 180,
                        'neutralMinionsKilled' => 12,
                        'goldEarned' => 14000,
                        'visionScore' => 28,
                        'individualPosition' => 'MIDDLE',
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function preparedMatchPayload(): array
    {
        return [
            'game' => Game::LeagueOfLegends->value,
            'match_id' => 'EUW1_MATCH_123',
            'riot_id' => 'TrackedPlayer#EUW',
            'title' => 'Ahri - victory',
            'summary_line' => 'TrackedPlayer secured a victory.',
            'color' => 0x22C55E,
            'finished_at' => CarbonImmutable::now()->toIso8601String(),
            'duration_seconds' => 1800,
            'player' => [
                'champion' => 'Ahri',
            ],
            'embed_fields' => [
                ['name' => 'Result', 'value' => 'victory', 'inline' => true],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function preparedDiscordEmbed(): array
    {
        return [
            'title' => 'Ahri - victory',
            'description' => 'TrackedPlayer secured a victory.',
            'color' => 0x22C55E,
            'fields' => [
                ['name' => 'Result', 'value' => 'victory', 'inline' => true],
            ],
            'footer' => [
                'text' => 'Gamesentry',
            ],
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ];
    }
}
