<?php

namespace Tests\Feature\Discord;

use App\Models\DiscordServer;
use App\Models\User;
use App\Models\WatchedPlayer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WatchedPlayerProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.discord.bot_token' => 'discord-bot-token',
            'services.riot.lol_key' => 'riot-lol-key',
            'services.riot.data_dragon_version' => '16.17.1',
        ]);

        Http::preventStrayRequests();
    }

    public function test_creating_a_league_player_stores_profile_icon_metadata(): void
    {
        $user = User::factory()->pro()->create();
        $server = DiscordServer::factory()->for($user)->create([
            'discord_guild_id' => '123456789012345678',
        ]);

        $this->fakeLeagueProfileRequests();

        $this->actingAs($user)
            ->post(route('dashboard.discord.servers.watched-players.store', $server), [
                'game' => 'lol',
                'routing_region' => 'europe',
                'game_name' => 'OldName',
                'tag_line' => 'euw',
                'discord_user_id' => '987654321098765432',
                'is_active' => true,
            ])
            ->assertRedirect(route('dashboard.discord.servers.show', $server));

        $this->assertDatabaseHas('watched_players', [
            'discord_server_id' => $server->id,
            'game_name' => 'ResolvedName',
            'tag_line' => 'EUW',
            'riot_puuid' => 'player-puuid',
            'profile_icon_id' => 4567,
        ]);

        $player = $server->watchedPlayers()->sole();
        $this->assertNotNull($player->profile_refreshed_at);
    }

    public function test_manual_refresh_updates_profile_metadata_without_changing_polling_state(): void
    {
        $user = User::factory()->create();
        $server = DiscordServer::factory()->for($user)->create();
        $player = WatchedPlayer::factory()->for($server)->create([
            'game_name' => 'OldName',
            'tag_line' => 'OLD',
            'riot_puuid' => 'player-puuid',
            'profile_icon_id' => 123,
            'last_seen_match_id' => 'EUW1_987654321',
            'last_polled_at' => '2026-09-10 10:00:00',
            'next_poll_at' => '2026-09-10 10:05:00',
            'poll_interval_seconds' => 300,
        ]);

        Http::fake([
            'https://europe.api.riotgames.com/riot/account/v1/accounts/by-puuid/player-puuid' => Http::response([
                'puuid' => 'player-puuid',
                'gameName' => 'CurrentName',
                'tagLine' => 'EUW',
            ]),
            'https://euw1.api.riotgames.com/lol/summoner/v4/summoners/by-puuid/player-puuid' => Http::response([
                'id' => 'summoner-id',
                'accountId' => 'account-id',
                'puuid' => 'player-puuid',
                'profileIconId' => 9876,
                'revisionDate' => 1_700_000_000_000,
                'summonerLevel' => 123,
            ]),
        ]);

        $this->actingAs($user)
            ->post(route('dashboard.discord.servers.watched-players.refresh', [$server, $player]))
            ->assertRedirect(route('dashboard.discord.servers.watched-players.edit', [$server, $player]));

        $player->refresh();

        $this->assertSame('CurrentName', $player->game_name);
        $this->assertSame('EUW', $player->tag_line);
        $this->assertSame(9876, $player->profile_icon_id);
        $this->assertSame('EUW1_987654321', $player->last_seen_match_id);
        $this->assertSame('2026-09-10 10:00:00', $player->last_polled_at?->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-10 10:05:00', $player->next_poll_at?->format('Y-m-d H:i:s'));
        $this->assertSame(300, $player->poll_interval_seconds);
        $this->assertNotNull($player->profile_refreshed_at);
    }

    public function test_updating_a_league_identity_refreshes_profile_icon_metadata(): void
    {
        $user = User::factory()->create();
        $server = DiscordServer::factory()->for($user)->create([
            'discord_guild_id' => '123456789012345678',
        ]);
        $player = WatchedPlayer::factory()->for($server)->create([
            'riot_puuid' => 'previous-puuid',
            'profile_icon_id' => 123,
        ]);

        $this->fakeLeagueProfileRequests();

        $this->actingAs($user)
            ->patch(route('dashboard.discord.servers.watched-players.update', [$server, $player]), [
                'game' => 'lol',
                'routing_region' => 'europe',
                'game_name' => 'OldName',
                'tag_line' => 'euw',
                'discord_user_id' => '987654321098765432',
                'is_active' => true,
            ])
            ->assertRedirect(route('dashboard.discord.servers.show', $server));

        $player->refresh();

        $this->assertSame('player-puuid', $player->riot_puuid);
        $this->assertSame(4567, $player->profile_icon_id);
        $this->assertNotNull($player->profile_refreshed_at);
    }

    public function test_users_cannot_refresh_another_users_tracked_player(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $server = DiscordServer::factory()->for($otherUser)->create();
        $player = WatchedPlayer::factory()->for($server)->create();

        $this->actingAs($user)
            ->post(route('dashboard.discord.servers.watched-players.refresh', [$server, $player]))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    private function fakeLeagueProfileRequests(): void
    {
        Http::fake([
            'https://discord.com/api/v10/guilds/123456789012345678/members/987654321098765432' => Http::response([]),
            'https://europe.api.riotgames.com/riot/account/v1/accounts/by-riot-id/OldName/EUW' => Http::response([
                'puuid' => 'player-puuid',
                'gameName' => 'ResolvedName',
                'tagLine' => 'EUW',
            ]),
            'https://europe.api.riotgames.com/lol/match/v5/matches/by-puuid/player-puuid/ids*' => Http::response([
                'EUW1_987654321',
            ]),
            'https://euw1.api.riotgames.com/lol/summoner/v4/summoners/by-puuid/player-puuid' => Http::response([
                'id' => 'summoner-id',
                'accountId' => 'account-id',
                'puuid' => 'player-puuid',
                'profileIconId' => 4567,
                'revisionDate' => 1_700_000_000_000,
                'summonerLevel' => 123,
            ]),
        ]);
    }
}
