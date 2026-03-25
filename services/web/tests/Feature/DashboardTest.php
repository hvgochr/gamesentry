<?php

namespace Tests\Feature;

use App\Models\DiscordServer;
use App\Models\TrackedPlayer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $user = User::factory()->create();
        $server = DiscordServer::factory()->for($user)->create([
            'roast_enabled' => true,
        ]);

        TrackedPlayer::factory()->for($server)->create([
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->where('stats.serverCount', 1)
                ->where('stats.playerCount', 1)
                ->where('stats.activePlayerCount', 1)
                ->where('stats.roastEnabledCount', 1)
                ->has('servers', 1)
                ->has('trackedPlayers', 1),
            );
    }
}
