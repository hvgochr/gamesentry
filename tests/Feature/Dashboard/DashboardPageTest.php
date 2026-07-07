<?php

namespace Tests\Feature\Dashboard;

use App\Models\DiscordServer;
use App\Models\MatchNotification;
use App\Models\User;
use App\Models\WatchedPlayer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('dashboard.index'))
            ->assertRedirect(route('login'));
    }

    public function test_dashboard_shows_only_the_authenticated_users_operational_summary(): void
    {
        $user = User::factory()->create();
        $server = DiscordServer::factory()->for($user)->create([
            'name' => 'User Server',
        ]);
        $activePlayer = WatchedPlayer::factory()->for($server)->create([
            'game_name' => 'ActivePlayer',
        ]);
        WatchedPlayer::factory()->inactive()->for($server)->create();

        MatchNotification::factory()->sent()->create([
            'watched_player_id' => $activePlayer->id,
        ]);
        MatchNotification::factory()->pending()->create([
            'watched_player_id' => $activePlayer->id,
        ]);
        MatchNotification::factory()->failed()->create([
            'watched_player_id' => $activePlayer->id,
        ]);

        $otherUser = User::factory()->create();
        $otherServer = DiscordServer::factory()->for($otherUser)->create([
            'name' => 'Other Server',
        ]);
        $otherPlayer = WatchedPlayer::factory()->for($otherServer)->create();
        MatchNotification::factory()->sent()->create([
            'watched_player_id' => $otherPlayer->id,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->where('stats.servers_count', 1)
                ->where('stats.watched_players_count', 2)
                ->where('stats.active_watched_players_count', 1)
                ->where('stats.sent_notifications_count', 1)
                ->where('stats.pending_notifications_count', 1)
                ->where('stats.failed_notifications_count', 1)
                ->has('recentNotifications', 3)
                ->where('navigation.discord_servers.0.name', 'User Server'));
    }
}
