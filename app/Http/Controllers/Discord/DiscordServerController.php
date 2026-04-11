<?php

namespace App\Http\Controllers\Discord;

use App\Enums\Game;
use App\Http\Controllers\Controller;
use App\Http\Requests\Discord\UpdateDiscordServerRequest;
use App\Models\DiscordServer;
use App\Models\MatchNotification;
use App\Models\WatchedPlayer;
use App\Services\Discord\DiscordService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class DiscordServerController extends Controller
{
    public function index(Request $request, DiscordService $discord): Response
    {
        $user = $request->user();

        return Inertia::render('discord/index', [
            'discordConfigured' => $discord->isConfigured(),
            'servers' => $user->discordServers()
                ->withCount('watchedPlayers')
                ->latest('name')
                ->get()
                ->map(function (DiscordServer $server) use ($discord) {
                    try {
                        $guild = $discord->installedGuild(
                            guildId: $server->discord_guild_id,
                            fallbackName: $server->name,
                            fallbackIcon: $server->icon,
                        );
                    } catch (RuntimeException $exception) {
                        $guild = [
                            'channels' => [],
                            'bot_installed' => false,
                            'sync_error' => $exception->getMessage(),
                        ];
                    }

                    return [
                        'id' => $server->id,
                        'name' => $server->name,
                        'discord_guild_id' => $server->discord_guild_id,
                        'discord_channel_id' => $server->discord_channel_id,
                        'discord_channel_name' => $server->discord_channel_name,
                        'icon_url' => $discord->guildIconUrl($server->discord_guild_id, $server->icon),
                        'watched_players_count' => $server->watched_players_count,
                        'channels' => $guild['channels'],
                        'bot_installed' => $guild['bot_installed'],
                        'sync_error' => $guild['sync_error'],
                    ];
                })
                ->values()
                ->all(),
            'status' => $request->session()->get('status'),
            'error' => $request->session()->get('error'),
        ]);
    }

    public function show(Request $request, DiscordServer $discordServer): Response
    {
        Gate::authorize('view', $discordServer);

        return Inertia::render('discord/servers/show', [
            'server' => [
                'id' => $discordServer->id,
                'name' => $discordServer->name,
                'discord_guild_id' => $discordServer->discord_guild_id,
                'discord_channel_name' => $discordServer->discord_channel_name,
            ],
            'watchedPlayers' => $discordServer->watchedPlayers()
                ->latest('game_name')
                ->get()
                ->map(fn (WatchedPlayer $watchedPlayer) => [
                    'id' => $watchedPlayer->id,
                    'game' => $watchedPlayer->game->value,
                    'routing_region' => $watchedPlayer->routing_region,
                    'game_name' => $watchedPlayer->game_name,
                    'tag_line' => $watchedPlayer->tag_line,
                    'discord_user_id' => $watchedPlayer->discord_user_id,
                    'last_seen_match_id' => $watchedPlayer->last_seen_match_id,
                    'last_polled_at' => $watchedPlayer->last_polled_at?->toIso8601String(),
                    'next_poll_at' => $watchedPlayer->next_poll_at?->toIso8601String(),
                    'poll_interval_seconds' => $watchedPlayer->poll_interval_seconds,
                    'is_active' => $watchedPlayer->is_active,
                ])
                ->values()
                ->all(),
            'gameOptions' => collect(Game::cases())
                ->map(fn (Game $game) => [
                    'value' => $game->value,
                    'label' => match ($game) {
                        Game::LeagueOfLegends => 'League of Legends',
                        Game::TeamfightTactics => 'Teamfight Tactics',
                    },
                ])
                ->values()
                ->all(),
            'routingRegionOptions' => [
                ['value' => 'americas', 'label' => 'Americas'],
                ['value' => 'asia', 'label' => 'Asia'],
                ['value' => 'europe', 'label' => 'Europe'],
                ['value' => 'sea', 'label' => 'SEA'],
            ],
            'status' => $request->session()->get('status'),
            'error' => $request->session()->get('error'),
            'recentNotifications' => MatchNotification::query()
                ->with('watchedPlayer:id,game_name,tag_line')
                ->whereBelongsTo($discordServer)
                ->latest()
                ->limit(8)
                ->get()
                ->map(fn (MatchNotification $notification) => [
                    'id' => $notification->id,
                    'game' => $notification->game->value,
                    'riot_match_id' => $notification->riot_match_id,
                    'status' => $notification->status->value,
                    'player_name' => $notification->watchedPlayer === null
                        ? null
                        : "{$notification->watchedPlayer->game_name}#{$notification->watchedPlayer->tag_line}",
                    'roast_text' => $notification->roast_text,
                    'failure_reason' => $notification->failure_reason,
                    'delivered_at' => $notification->delivered_at?->toIso8601String(),
                    'created_at' => $notification->created_at?->toIso8601String(),
                ])
                ->values()
                ->all(),
        ]);
    }

    public function update(
        UpdateDiscordServerRequest $request,
        DiscordServer $discordServer,
        DiscordService $discord,
    ): RedirectResponse {
        Gate::authorize('update', $discordServer);

        $guild = $this->resolveInstalledGuildOrFail(
            $discord,
            $discordServer,
        );

        $channel = $this->resolveChannelOrFail($guild, (string) $request->string('discord_channel_id'));

        $discordServer->forceFill([
            'name' => $guild['name'],
            'icon' => $guild['icon'],
            'discord_channel_id' => $channel['id'],
            'discord_channel_name' => $channel['name'],
            'bot_installed_at' => now(),
            'settings_synced_at' => now(),
        ])->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Discord server updated.',
        ]);

        return to_route('discord.index');
    }

    public function destroy(Request $request, DiscordServer $discordServer): RedirectResponse
    {
        Gate::authorize('delete', $discordServer);

        $discordServer->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Discord server deleted.',
        ]);

        return to_route('discord.index');
    }

    /**
     * @param  array{id: string, channels: list<array{id: string, name: string}>, bot_installed: bool, claimed_by_another_user?: bool}  $guild
     * @return array{id: string, name: string}
     */
    private function resolveChannelOrFail(array $guild, string $channelId): array
    {
        $channel = collect($guild['channels'])->firstWhere('id', $channelId);

        if ($channel === null) {
            throw ValidationException::withMessages([
                'discord_channel_id' => 'Select a valid text channel.',
            ]);
        }

        return $channel;
    }

    /**
     * @return array{
     *     id: string,
     *     name: string,
     *     icon: string|null,
     *     bot_installed: bool,
     *     channels: list<array{id: string, name: string}>,
     *     sync_error: string|null
     * }
     */
    private function resolveInstalledGuildOrFail(
        DiscordService $discord,
        DiscordServer $discordServer,
    ): array {
        try {
            $guild = $discord->installedGuild(
                guildId: $discordServer->discord_guild_id,
                fallbackName: $discordServer->name,
                fallbackIcon: $discordServer->icon,
            );
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'discord_channel_id' => $exception->getMessage(),
            ]);
        }

        if (! $guild['bot_installed']) {
            throw ValidationException::withMessages([
                'discord_channel_id' => 'Add the Discord bot to this server before updating.',
            ]);
        }

        if ($guild['sync_error'] !== null) {
            throw ValidationException::withMessages([
                'discord_channel_id' => $guild['sync_error'],
            ]);
        }

        return $guild;
    }
}
