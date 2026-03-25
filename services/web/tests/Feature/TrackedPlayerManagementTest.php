<?php

namespace Tests\Feature;

use App\Models\DiscordServer;
use App\Models\TrackedPlayer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TrackedPlayerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_users_can_view_the_players_page()
    {
        $user = User::factory()->create();
        $server = DiscordServer::factory()->for($user)->create();

        TrackedPlayer::factory()->for($server)->create();

        $this->actingAs($user)
            ->get(route('players.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('players/index')
                ->has('servers', 1)
                ->has('trackedPlayers', 1)
                ->has('gameOptions', 2),
            );
    }

    public function test_users_can_create_tracked_players_for_their_own_servers()
    {
        $user = User::factory()->create();
        $server = DiscordServer::factory()->for($user)->create();

        $response = $this->actingAs($user)->post(route('players.store'), [
            'discord_server_id' => $server->id,
            'game' => 'lol',
            'riot_name' => 'Faker',
            'riot_tagline' => 'euw',
            'region' => 'EUW',
            'discord_user_id' => '123456789012345678',
            'is_active' => '1',
        ]);

        $response
            ->assertRedirect(route('players.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tracked_players', [
            'discord_server_id' => $server->id,
            'game' => 'lol',
            'riot_name' => 'Faker',
            'riot_tagline' => 'EUW',
            'region' => 'euw',
            'routing_region' => 'europe',
            'discord_user_id' => '123456789012345678',
            'is_active' => true,
        ]);
    }

    public function test_users_can_update_their_own_tracked_players()
    {
        $user = User::factory()->create();
        $server = DiscordServer::factory()->for($user)->create();
        $trackedPlayer = TrackedPlayer::factory()->for($server)->create();

        $response = $this->actingAs($user)->patch(route('players.update', $trackedPlayer), [
            'discord_server_id' => $server->id,
            'game' => 'lol',
            'riot_name' => 'NewName',
            'riot_tagline' => 'na1',
            'region' => 'NA',
            'discord_user_id' => '555555555555555555',
            'is_active' => '0',
        ]);

        $response
            ->assertRedirect(route('players.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tracked_players', [
            'id' => $trackedPlayer->id,
            'game' => 'lol',
            'riot_name' => 'NewName',
            'riot_tagline' => 'NA1',
            'region' => 'na',
            'routing_region' => 'americas',
            'discord_user_id' => '555555555555555555',
            'is_active' => false,
        ]);
    }

    public function test_users_cannot_track_players_on_another_users_servers()
    {
        $user = User::factory()->create();
        $otherUsersServer = DiscordServer::factory()->create();

        $response = $this->actingAs($user)->post(route('players.store'), [
            'discord_server_id' => $otherUsersServer->id,
            'game' => 'tft',
            'riot_name' => 'BlockedPlayer',
            'riot_tagline' => 'EUW',
            'region' => 'euw',
            'discord_user_id' => '123456789012345678',
            'is_active' => '1',
        ]);

        $response->assertSessionHasErrors('discord_server_id');

        $this->assertDatabaseCount('tracked_players', 0);
    }
}
