<?php

namespace App\Http\Controllers\Discord;

use App\Enums\Game;
use App\Http\Controllers\Controller;
use App\Http\Requests\Discord\StoreWatchedPlayerRequest;
use App\Http\Requests\Discord\UpdateWatchedPlayerRequest;
use App\Models\DiscordServer;
use App\Models\User;
use App\Models\WatchedPlayer;
use App\Services\Discord\DiscordService;
use App\Services\Plans\Exceptions\PlanLimitExceededException;
use App\Services\Plans\PlanLimitService;
use App\Services\Riot\RiotApiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use RuntimeException;

class WatchedPlayerController extends Controller
{
    public function store(
        StoreWatchedPlayerRequest $request,
        DiscordServer $discordServer,
        DiscordService $discord,
        RiotApiService $riot,
        PlanLimitService $limits,
    ): RedirectResponse {
        Gate::authorize('update', $discordServer);

        $this->validateWatchedPlayerLimit($request->user(), $limits);

        $payload = $this->resolveWatchedPlayerPayload(
            $request->validatedPayload(),
            $discordServer,
            $discord,
            $riot,
        );

        DB::transaction(function () use ($request, $discordServer, $payload, $limits): void {
            $user = User::query()
                ->whereKey($request->user()->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->validateWatchedPlayerLimit($user, $limits);

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
        }, 3);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Player added to monitoring.',
        ]);

        return to_route('dashboard.discord.servers.show', $discordServer);
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
            $request->validatedPayload(),
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

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Monitored player updated.',
        ]);

        return to_route('dashboard.discord.servers.show', $discordServer);
    }

    public function destroy(
        Request $request,
        DiscordServer $discordServer,
        WatchedPlayer $watchedPlayer,
    ): RedirectResponse {
        Gate::authorize('delete', $watchedPlayer);

        $watchedPlayer->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Monitored player removed.',
        ]);

        return to_route('dashboard.discord.servers.show', $discordServer);
    }

    /**
     * @param  array{game: string, routing_region: string, game_name: string, tag_line: string, discord_user_id: string, is_active: bool}  $validated
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
        $isActive = $validated['is_active'];

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

    private function validateWatchedPlayerLimit(User $user, PlanLimitService $limits): void
    {
        try {
            $limits->ensureCanCreateWatchedPlayer($user);
        } catch (PlanLimitExceededException $exception) {
            throw ValidationException::withMessages([
                'game_name' => $exception->getMessage(),
            ]);
        }
    }
}
