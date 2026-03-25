<?php

namespace Tests\Feature;

use App\Models\DiscordServer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DiscordServerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_users_can_view_the_servers_page()
    {
        $user = User::factory()->create();

        DiscordServer::factory()->for($user)->create();

        $this->actingAs($user)
            ->get(route('servers.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('servers/index')
                ->has('servers', 1)
                ->where('summary.serverCount', 1),
            );
    }

    public function test_users_can_create_servers()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('servers.store'), [
            'name' => 'Gamesentry EU',
            'discord_guild_id' => '123456789012345678',
            'alert_channel_name' => 'match-feed',
            'alert_channel_id' => '987654321098765432',
            'roast_enabled' => '1',
        ]);

        $response
            ->assertRedirect(route('servers.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('discord_servers', [
            'user_id' => $user->id,
            'name' => 'Gamesentry EU',
            'discord_guild_id' => '123456789012345678',
            'alert_channel_name' => 'match-feed',
            'alert_channel_id' => '987654321098765432',
            'roast_enabled' => true,
        ]);
    }

    public function test_users_can_update_their_own_servers()
    {
        $user = User::factory()->create();
        $server = DiscordServer::factory()->for($user)->create();

        $response = $this->actingAs($user)->patch(route('servers.update', $server), [
            'name' => 'Updated Gamesentry EU',
            'discord_guild_id' => $server->discord_guild_id,
            'alert_channel_name' => 'riot-alerts',
            'alert_channel_id' => '555555555555555555',
            'roast_enabled' => '0',
        ]);

        $response
            ->assertRedirect(route('servers.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('discord_servers', [
            'id' => $server->id,
            'name' => 'Updated Gamesentry EU',
            'alert_channel_name' => 'riot-alerts',
            'alert_channel_id' => '555555555555555555',
            'roast_enabled' => false,
        ]);
    }

    public function test_users_cannot_update_another_users_servers()
    {
        $user = User::factory()->create();
        $otherUsersServer = DiscordServer::factory()->create();

        $this->actingAs($user)
            ->patch(route('servers.update', $otherUsersServer), [
                'name' => 'Not allowed',
                'discord_guild_id' => $otherUsersServer->discord_guild_id,
                'alert_channel_name' => 'match-feed',
                'alert_channel_id' => '111111111111111111',
                'roast_enabled' => '1',
            ])
            ->assertNotFound();
    }
}
