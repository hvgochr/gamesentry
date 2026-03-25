<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDiscordServerRequest;
use App\Http\Requests\UpdateDiscordServerRequest;
use App\Models\DiscordServer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DiscordServerController extends Controller
{
    public function index(Request $request): Response
    {
        $servers = $request->user()
            ->discordServers()
            ->withCount('trackedPlayers')
            ->latest()
            ->get();

        return Inertia::render('servers/index', [
            'summary' => [
                'serverCount' => $servers->count(),
                'trackedPlayerCount' => (int) $servers->sum('tracked_players_count'),
            ],
            'servers' => $servers
                ->map(fn (DiscordServer $server) => [
                    'id' => $server->id,
                    'name' => $server->name,
                    'discord_guild_id' => $server->discord_guild_id,
                    'alert_channel_name' => $server->alert_channel_name,
                    'alert_channel_id' => $server->alert_channel_id,
                    'roast_enabled' => $server->roast_enabled,
                    'tracked_players_count' => $server->tracked_players_count,
                ])
                ->values(),
        ]);
    }

    public function store(StoreDiscordServerRequest $request): RedirectResponse
    {
        $request->user()->discordServers()->create($request->validated());

        return to_route('servers.index');
    }

    public function update(UpdateDiscordServerRequest $request, DiscordServer $discordServer): RedirectResponse
    {
        $server = $request->user()->discordServers()->findOrFail($discordServer->getKey());

        $server->update($request->validated());

        return to_route('servers.index');
    }

    public function destroy(Request $request, DiscordServer $discordServer): RedirectResponse
    {
        $server = $request->user()->discordServers()->findOrFail($discordServer->getKey());

        $server->delete();

        return to_route('servers.index');
    }
}
