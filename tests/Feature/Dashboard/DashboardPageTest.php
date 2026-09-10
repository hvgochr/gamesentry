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
        config()->set('services.riot.data_dragon_version', '16.17.1');

        $user = User::factory()->create();
        $server = DiscordServer::factory()->for($user)->create([
            'name' => 'User Server',
        ]);
        $activePlayer = WatchedPlayer::factory()->for($server)->create([
            'game_name' => 'ActivePlayer',
            'profile_icon_id' => 4567,
        ]);
        WatchedPlayer::factory()->inactive()->for($server)->create();

        MatchNotification::factory()->sent()->create([
            'watched_player_id' => $activePlayer->id,
            'match_payload' => [
                'player' => ['champion' => 'Aatrox'],
            ],
        ]);
        MatchNotification::factory()->pending()->create([
            'watched_player_id' => $activePlayer->id,
            'match_payload' => [
                'player' => ['champion' => 'Aatrox'],
            ],
        ]);
        MatchNotification::factory()->failed()->create([
            'watched_player_id' => $activePlayer->id,
            'match_payload' => [
                'player' => ['champion' => 'Aatrox'],
            ],
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
                ->where(
                    'recentNotifications.0.profile_icon_url',
                    'https://ddragon.leagueoflegends.com/cdn/16.17.1/img/profileicon/4567.png',
                )
                ->where(
                    'recentNotifications.0.champion_icon_url',
                    'https://ddragon.leagueoflegends.com/cdn/16.17.1/img/champion/Aatrox.png',
                )
                ->where('navigation.discord_servers.0.name', 'User Server'));
    }
}
