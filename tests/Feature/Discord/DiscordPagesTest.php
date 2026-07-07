<?php

namespace Tests\Feature\Discord;

use App\Models\DiscordServer;
use App\Models\User;
use App\Models\WatchedPlayer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DiscordPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_server_index_and_create_pages_are_displayed(): void
    {
        config([
            'services.discord.client_id' => 'discord-client-id',
            'services.discord.redirect' => 'https://gamesentry.test/discord/callback',
            'services.discord.bot_token' => 'discord-bot-token',
        ]);

        $user = User::factory()->create();

        DiscordServer::factory()->for($user)->create([
            'name' => 'Ranked Lounge',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard.discord.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('discord/index')
                ->where('discordConfigured', true)
                ->where('canCreateServer', false)
                ->has('servers', 1)
                ->where('servers.0.name', 'Ranked Lounge'));

        $this->actingAs($user)
            ->get(route('dashboard.discord.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('discord/create')
                ->where('discordConfigured', true)
                ->where('canCreateServer', false)
                ->where('currentServerCount', 1));
    }

    public function test_server_detail_and_edit_pages_are_displayed(): void
    {
        config([
            'services.discord.client_id' => 'discord-client-id',
            'services.discord.redirect' => 'https://gamesentry.test/discord/callback',
            'services.discord.bot_token' => 'discord-bot-token',
        ]);

        $user = User::factory()->create();
        $server = DiscordServer::factory()->for($user)->create([
            'discord_guild_id' => '123456789012345678',
            'discord_channel_id' => '111111111111111111',
            'discord_channel_name' => 'clips',
            'name' => 'Ranked Lounge',
        ]);

        WatchedPlayer::factory()->for($server)->create([
            'game_name' => 'Hugo',
            'tag_line' => 'EUW',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard.discord.servers.show', $server))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('discord/servers/show')
                ->where('server.name', 'Ranked Lounge')
                ->has('watchedPlayers', 1)
                ->where('watchedPlayers.0.game_name', 'Hugo'));

        Http::fake([
            'https://discord.com/api/v10/guilds/123456789012345678' => Http::response([
                'id' => '123456789012345678',
                'name' => 'Ranked Lounge',
                'icon' => null,
            ]),
            'https://discord.com/api/v10/guilds/123456789012345678/channels' => Http::response([
                ['id' => '111111111111111111', 'name' => 'clips', 'type' => 0, 'position' => 0],
                ['id' => '222222222222222222', 'name' => 'roasts', 'type' => 0, 'position' => 1],
            ]),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard.discord.servers.edit', $server))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('discord/servers/edit')
                ->where('server.name', 'Ranked Lounge')
                ->where('botInstalled', true)
                ->has('channels', 2));
    }

    public function test_watched_player_create_and_edit_pages_are_displayed(): void
    {
        $user = User::factory()->pro()->create();
        $server = DiscordServer::factory()->for($user)->create([
            'name' => 'Ranked Lounge',
        ]);
        $watchedPlayer = WatchedPlayer::factory()->for($server)->create([
            'game_name' => 'Hugo',
            'tag_line' => 'EUW',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard.discord.servers.watched-players.create', $server))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('discord/servers/watched-players/create')
                ->where('server.name', 'Ranked Lounge')
                ->where('canCreateWatchedPlayer', true)
                ->has('gameOptions', 2));

        $this->actingAs($user)
            ->get(route('dashboard.discord.servers.watched-players.edit', [$server, $watchedPlayer]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('discord/servers/watched-players/edit')
                ->where('watchedPlayer.game_name', 'Hugo')
                ->where('watchedPlayer.tag_line', 'EUW'));
    }

    public function test_users_cannot_view_another_users_discord_pages(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $server = DiscordServer::factory()->for($otherUser)->create();
        $watchedPlayer = WatchedPlayer::factory()->for($server)->create();

        $this->actingAs($user)
            ->get(route('dashboard.discord.servers.show', $server))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('dashboard.discord.servers.watched-players.edit', [$server, $watchedPlayer]))
            ->assertForbidden();
    }
}
