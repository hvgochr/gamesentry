<?php

namespace App\Http\Controllers\Discord;

use App\Enums\Game;
use App\Http\Controllers\Controller;
use App\Http\Requests\Discord\StoreWatchedPlayerRequest;
use App\Http\Requests\Discord\UpdateWatchedPlayerRequest;
use App\Models\DiscordServer;
use App\Models\WatchedPlayer;
use App\Services\Discord\DiscordService;
use App\Services\Riot\RiotApiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class WatchedPlayerController extends Controller
{
    public function store(
        StoreWatchedPlayerRequest $request,
        DiscordServer $discordServer,
        DiscordService $discord,
        RiotApiService $riot,
    ): RedirectResponse {
        Gate::authorize('update', $discordServer);

        $payload = $this->resolveWatchedPlayerPayload(
            $request->validated(),
            $discordServer,
            $discord,
            $riot,
        );

        if ($discordServer->watchedPlayers()
            ->where('game', $payload['game']->value)
            ->where('riot_puuid', $payload['riot_puuid'])
            ->exists()) {
            throw ValidationException::withMessages([
                'game_name' => 'This player is already being monitored for this game on this server.',
            ]);
        }

        $discordServer->watchedPlayers()->create([
            'game' => $payload['game'],
            'routing_region' => $payload['routing_region'],
            'game_name' => $payload['game_name'],
            'tag_line' => $payload['tag_line'],
            'riot_puuid' => $payload['riot_puuid'],
            'discord_user_id' => $payload['discord_user_id'],
            'last_seen_match_id' => $payload['last_seen_match_id'],
            'last_polled_at' => null,
            'next_poll_at' => now()->addSeconds(300),
            'poll_interval_seconds' => 300,
            'is_active' => $payload['is_active'],
        ]);

        return to_route('discord.servers.show', $discordServer)
            ->with('status', 'Player added to monitoring.');
    }

    public function update(
        UpdateWatchedPlayerRequest $request,
        DiscordServer $discordServer,
        WatchedPlayer $watchedPlayer,
        DiscordService $discord,
        RiotApiService $riot,
    ): RedirectResponse {
        Gate::authorize('update', $watchedPlayer);

        $payload = $this->resolveWatchedPlayerPayload(
            $request->validated(),
            $discordServer,
            $discord,
            $riot,
        );

        if ($discordServer->watchedPlayers()
            ->whereKeyNot($watchedPlayer->id)
            ->where('game', $payload['game']->value)
            ->where('riot_puuid', $payload['riot_puuid'])
            ->exists()) {
            throw ValidationException::withMessages([
                'game_name' => 'This player is already being monitored for this game on this server.',
            ]);
        }

        $watchedPlayer->forceFill([
            'game' => $payload['game'],
            'routing_region' => $payload['routing_region'],
            'game_name' => $payload['game_name'],
            'tag_line' => $payload['tag_line'],
            'riot_puuid' => $payload['riot_puuid'],
            'discord_user_id' => $payload['discord_user_id'],
            'last_seen_match_id' => $payload['last_seen_match_id'],
            'next_poll_at' => now()->addSeconds($watchedPlayer->poll_interval_seconds),
            'is_active' => $payload['is_active'],
        ])->save();

        return to_route('discord.servers.show', $discordServer)
            ->with('status', 'Monitored player updated.');
    }

    public function destroy(
        Request $request,
        DiscordServer $discordServer,
        WatchedPlayer $watchedPlayer,
    ): RedirectResponse {
        Gate::authorize('delete', $watchedPlayer);

        $watchedPlayer->delete();

        return to_route('discord.servers.show', $discordServer)
            ->with('status', 'Monitored player removed.');
    }

    /**
     * @param  array{game: string, routing_region: string, game_name: string, tag_line: string, discord_user_id: string, is_active?: bool|string}  $validated
     * @return array{
     *     game: Game,
     *     routing_region: string,
     *     game_name: string,
     *     tag_line: string,
     *     riot_puuid: string,
     *     discord_user_id: string,
     *     last_seen_match_id: string|null,
     *     is_active: bool
     * }
     */
    private function resolveWatchedPlayerPayload(
        array $validated,
        DiscordServer $discordServer,
        DiscordService $discord,
        RiotApiService $riot,
    ): array {
        $game = Game::from($validated['game']);
        $routingRegion = strtolower($validated['routing_region']);
        $gameName = trim($validated['game_name']);
        $tagLine = strtoupper(trim($validated['tag_line']));
        $discordUserId = trim($validated['discord_user_id']);
        $isActive = filter_var($validated['is_active'] ?? true, FILTER_VALIDATE_BOOL);

        try {
            $memberExists = $discord->guildMemberExists(
                $discordServer->discord_guild_id,
                $discordUserId,
            );
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'discord_user_id' => $exception->getMessage(),
            ]);
        }

        if (! $memberExists) {
            throw ValidationException::withMessages([
                'discord_user_id' => 'The specified Discord user ID does not belong to a member of this server.',
            ]);
        }

        try {
            $account = $riot->resolveAccountByRiotId(
                $game,
                $routingRegion,
                $gameName,
                $tagLine,
            );
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'game_name' => $exception->getMessage(),
            ]);
        }

        if ($account === null) {
            throw ValidationException::withMessages([
                'game_name' => 'There is no Riot account associated with this Riot ID.',
                'tag_line' => 'Check the tagline provided.',
            ]);
        }

        try {
            $latestMatchId = $riot->latestMatchId(
                $game,
                $routingRegion,
                $account['puuid'],
            );
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'game_name' => $exception->getMessage(),
            ]);
        }

        return [
            'game' => $game,
            'routing_region' => $routingRegion,
            'game_name' => $account['gameName'] ?? $gameName,
            'tag_line' => $account['tagLine'] ?? $tagLine,
            'riot_puuid' => $account['puuid'],
            'discord_user_id' => $discordUserId,
            'last_seen_match_id' => $latestMatchId,
            'is_active' => $isActive,
        ];
    }
}
