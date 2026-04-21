<?php

namespace Tests\Unit\Services;

use App\Services\Discord\DiscordService;
use RuntimeException;
use Tests\TestCase;

class DiscordServiceUnitTest extends TestCase
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
    }

    public function test_is_configured_requires_client_id_redirect_uri_and_bot_token(): void
    {
        $service = app(DiscordService::class);

        $this->assertTrue($service->isConfigured());

        config()->set('services.discord.bot_token', null);
        $this->assertFalse($service->isConfigured());

        config()->set('services.discord.bot_token', 'discord-bot-token');
        config()->set('services.discord.redirect', null);
        $this->assertFalse($service->isConfigured());
    }

    public function test_installation_url_omits_guild_specific_parameters_when_no_guild_is_selected(): void
    {
        $url = app(DiscordService::class)->installationUrl('state-123');

        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        $this->assertSame('discord-client-id', $query['client_id']);
        $this->assertSame('state-123', $query['state']);
        $this->assertArrayNotHasKey('guild_id', $query);
        $this->assertArrayNotHasKey('disable_guild_select', $query);
        $this->assertSame('consent', $query['prompt']);
    }

    public function test_guild_icon_url_returns_null_for_blank_hashes_and_formats_valid_hashes(): void
    {
        $service = app(DiscordService::class);

        $this->assertNull($service->guildIconUrl('123456789012345678', null));
        $this->assertNull($service->guildIconUrl('123456789012345678', ''));
        $this->assertSame(
            'https://cdn.discordapp.com/icons/123456789012345678/icon-hash.png?size=128',
            $service->guildIconUrl('123456789012345678', 'icon-hash'),
        );
    }

    public function test_installation_url_throws_when_the_service_is_not_configured(): void
    {
        config()->set('services.discord.client_id', null);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Discord service is not properly configured.');

        app(DiscordService::class)->installationUrl('state-123');
    }
}
