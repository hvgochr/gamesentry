<?php

namespace App\Http\Controllers;

use App\Models\TrackedPlayer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $servers = $user->discordServers()
            ->withCount('trackedPlayers')
            ->latest()
            ->limit(4)
            ->get();

        $trackedPlayers = $user->trackedPlayers()
            ->with('discordServer')
            ->latest()
            ->limit(6)
            ->get();

        return Inertia::render('dashboard', [
            'stats' => [
                'serverCount' => $user->discordServers()->count(),
                'playerCount' => $user->trackedPlayers()->count(),
                'activePlayerCount' => $user->trackedPlayers()->where('is_active', true)->count(),
                'roastEnabledCount' => $user->discordServers()->where('roast_enabled', true)->count(),
            ],
            'servers' => $servers
                ->map(fn ($server) => [
                    'id' => $server->id,
                    'name' => $server->name,
                    'discord_guild_id' => $server->discord_guild_id,
                    'alert_channel_name' => $server->alert_channel_name,
                    'alert_channel_id' => $server->alert_channel_id,
                    'roast_enabled' => $server->roast_enabled,
                    'tracked_players_count' => $server->tracked_players_count,
                ])
                ->values(),
            'trackedPlayers' => $trackedPlayers
                ->map(fn ($trackedPlayer) => [
                    'id' => $trackedPlayer->id,
                    'riot_id' => $trackedPlayer->riot_name.'#'.$trackedPlayer->riot_tagline,
                    'game' => $trackedPlayer->game,
                    'game_label' => TrackedPlayer::labelForGame($trackedPlayer->game),
                    'region' => $trackedPlayer->region,
                    'discord_user_id' => $trackedPlayer->discord_user_id,
                    'is_active' => $trackedPlayer->is_active,
                    'server_name' => $trackedPlayer->discordServer?->name,
                ])
                ->values(),
        ]);
    }
}
