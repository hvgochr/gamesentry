<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTrackedPlayerRequest;
use App\Http\Requests\UpdateTrackedPlayerRequest;
use App\Models\DiscordServer;
use App\Models\TrackedPlayer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TrackedPlayerController extends Controller
{
    public function index(Request $request): Response
    {
        $servers = $request->user()
            ->discordServers()
            ->orderBy('name')
            ->get();

        $trackedPlayers = $request->user()
            ->trackedPlayers()
            ->with('discordServer')
            ->latest()
            ->get();

        return Inertia::render('players/index', [
            'summary' => [
                'playerCount' => $trackedPlayers->count(),
                'activePlayerCount' => $trackedPlayers->where('is_active', true)->count(),
            ],
            'servers' => $servers
                ->map(fn (DiscordServer $server) => [
                    'id' => $server->id,
                    'name' => $server->name,
                    'discord_guild_id' => $server->discord_guild_id,
                ])
                ->values(),
            'gameOptions' => TrackedPlayer::gameOptions(),
            'regionOptions' => TrackedPlayer::regionOptions(),
            'trackedPlayers' => $trackedPlayers
                ->map(fn (TrackedPlayer $trackedPlayer) => [
                    'id' => $trackedPlayer->id,
                    'discord_server_id' => $trackedPlayer->discord_server_id,
                    'server_name' => $trackedPlayer->discordServer?->name,
                    'game' => $trackedPlayer->game->value,
                    'game_label' => $trackedPlayer->game->label(),
                    'riot_name' => $trackedPlayer->riot_name,
                    'riot_tagline' => $trackedPlayer->riot_tagline,
                    'riot_id' => $trackedPlayer->riot_name.'#'.$trackedPlayer->riot_tagline,
                    'region' => $trackedPlayer->region->value,
                    'region_label' => $trackedPlayer->region->label(),
                    'routing_region' => $trackedPlayer->routing_region?->value,
                    'discord_user_id' => $trackedPlayer->discord_user_id,
                    'is_active' => $trackedPlayer->is_active,
                ])
                ->values(),
        ]);
    }

    public function store(StoreTrackedPlayerRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['routing_region'] = TrackedPlayer::routingRegionFor($validated['region']);

        TrackedPlayer::query()->create($validated);

        return to_route('players.index');
    }

    public function update(UpdateTrackedPlayerRequest $request, TrackedPlayer $trackedPlayer): RedirectResponse
    {
        $player = $request->user()->trackedPlayers()->findOrFail($trackedPlayer->getKey());

        $validated = $request->validated();
        $validated['routing_region'] = TrackedPlayer::routingRegionFor($validated['region']);

        $player->update($validated);

        return to_route('players.index');
    }

    public function destroy(Request $request, TrackedPlayer $trackedPlayer): RedirectResponse
    {
        $player = $request->user()->trackedPlayers()->findOrFail($trackedPlayer->getKey());

        $player->delete();

        return to_route('players.index');
    }
}
