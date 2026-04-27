<?php

namespace Tests\Feature\Services;

use App\Services\Discord\DiscordService;
use App\Services\Discord\Exceptions\DiscordDuplicateNonceException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class DiscordServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.discord.client_id' => 'discord-client-id',
            'services.discord.redirect' => 'https://gamesentry.test/discord/install/callback',
            'services.discord.bot_token' => 'discord-bot-token',
            'services.discord.bot_permissions' => '123456',
        ]);

        Http::preventStrayRequests();
    }

    public function test_installation_url_contains_the_expected_query_parameters(): void
    {
        $url = app(DiscordService::class)->installationUrl('state-123', 'guild-456');

        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        $this->assertSame('discord-client-id', $query['client_id']);
        $this->assertSame('https://gamesentry.test/discord/install/callback', $query['redirect_uri']);
        $this->assertSame('bot applications.commands', $query['scope']);
        $this->assertSame('123456', $query['permissions']);
        $this->assertSame('state-123', $query['state']);
        $this->assertSame('guild-456', $query['guild_id']);
        $this->assertSame('true', $query['disable_guild_select']);
        $this->assertSame('consent', $query['prompt']);
    }

    public function test_installed_guild_returns_not_installed_when_discord_cannot_find_the_guild(): void
    {
        Http::fake([
            'https://discord.com/api/v10/guilds/123456789012345678' => Http::response([], 404),
        ]);

        $guild = app(DiscordService::class)->installedGuild(
            '123456789012345678',
            'Fallback Guild',
            'icon-hash',
        );

        $this->assertFalse($guild['bot_installed']);
        $this->assertSame('Fallback Guild', $guild['name']);
        $this->assertSame([], $guild['channels']);
        $this->assertNull($guild['sync_error']);
        $this->assertSame(
            'https://cdn.discordapp.com/icons/123456789012345678/icon-hash.png?size=128',
            $guild['icon_url'],
        );
    }

    public function test_installed_guild_filters_supported_channels_and_sorts_them_by_position(): void
    {
        Http::fake([
            'https://discord.com/api/v10/guilds/123456789012345678' => Http::response([
                'id' => '123456789012345678',
                'name' => 'Gamesentry HQ',
                'icon' => 'guild-icon',
            ], 200),
            'https://discord.com/api/v10/guilds/123456789012345678/channels' => Http::response([
                ['id' => '3', 'name' => 'voice', 'type' => 2, 'position' => 1],
                ['id' => '2', 'name' => 'announcements', 'type' => 5, 'position' => 3],
                ['id' => '1', 'name' => 'general', 'type' => 0, 'position' => 2],
            ], 200),
        ]);

        $guild = app(DiscordService::class)->installedGuild('123456789012345678');

        $this->assertTrue($guild['bot_installed']);
        $this->assertSame([
            ['id' => '1', 'name' => 'general'],
            ['id' => '2', 'name' => 'announcements'],
        ], $guild['channels']);
        $this->assertNull($guild['sync_error']);
    }

    public function test_installed_guild_reports_a_sync_error_when_channels_cannot_be_loaded(): void
    {
        Http::fake([
            'https://discord.com/api/v10/guilds/123456789012345678' => Http::response([
                'id' => '123456789012345678',
                'name' => 'Gamesentry HQ',
                'icon' => null,
            ], 200),
            'https://discord.com/api/v10/guilds/123456789012345678/channels' => Http::response([], 500),
        ]);

        $guild = app(DiscordService::class)->installedGuild('123456789012345678');

        $this->assertTrue($guild['bot_installed']);
        $this->assertSame([], $guild['channels']);
        $this->assertSame('Unable to sync the Gamesentry HQ guild channels.', $guild['sync_error']);
    }

    public function test_guild_member_exists_returns_false_for_missing_members_and_throws_on_other_errors(): void
    {
        Http::fake([
            'https://discord.com/api/v10/guilds/123/members/111' => Http::response(['user' => ['id' => '111']], 200),
            'https://discord.com/api/v10/guilds/123/members/222' => Http::response([], 404),
            'https://discord.com/api/v10/guilds/123/members/333' => Http::response([], 500),
        ]);

        $this->assertTrue(app(DiscordService::class)->guildMemberExists('123', '111'));
        $this->assertFalse(app(DiscordService::class)->guildMemberExists('123', '222'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to verify Discord guild member existence.');

        app(DiscordService::class)->guildMemberExists('123', '333');
    }

    public function test_send_match_notification_returns_the_message_id_and_reuses_the_given_nonce(): void
    {
        Http::fake([
            'https://discord.com/api/v10/channels/123/messages' => Http::response([
                'id' => '998877665544332211',
            ], 200),
        ]);

        $messageId = app(DiscordService::class)->sendMatchNotification(
            '123',
            '456',
            'Roast text',
            ['title' => 'Title'],
            'nonce-123',
        );

        $this->assertSame('998877665544332211', $messageId);

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://discord.com/api/v10/channels/123/messages'
                && ($request->data()['nonce'] ?? null) === 'nonce-123'
                && ($request->data()['enforce_nonce'] ?? null) === true
                && ($request->data()['content'] ?? null) === '<@456> Roast text';
        });
    }

    public function test_send_match_notification_throws_a_duplicate_nonce_exception_for_enforced_duplicates(): void
    {
        Http::fake([
            'https://discord.com/api/v10/channels/123/messages' => Http::response([
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
            ], 400),
        ]);

        $this->expectException(DiscordDuplicateNonceException::class);

        app(DiscordService::class)->sendMatchNotification(
            '123',
            '456',
            'Roast text',
            ['title' => 'Title'],
            'nonce-123',
        );
    }
}
