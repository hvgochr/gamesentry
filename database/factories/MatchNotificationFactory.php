<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Game;
use App\Enums\MatchNotificationStatus;
use App\Models\DiscordServer;
use App\Models\MatchNotification;
use App\Models\WatchedPlayer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MatchNotification>
 */
class MatchNotificationFactory extends Factory
{
    public function configure(): static
    {
        return $this->afterMaking(function (MatchNotification $matchNotification): void {
            $watchedPlayerId = $matchNotification->watched_player_id;

            if (! is_int($watchedPlayerId) && ! (is_string($watchedPlayerId) && ctype_digit($watchedPlayerId))) {
                return;
            }

            $watchedPlayer = WatchedPlayer::query()->find($watchedPlayerId);

            if ($watchedPlayer === null) {
                return;
            }

            $matchNotification->discord_server_id = $watchedPlayer->discord_server_id;
            $matchNotification->game = $watchedPlayer->game;
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'watched_player_id' => WatchedPlayer::factory(),
            'discord_server_id' => DiscordServer::factory(),
            'game' => Game::LeagueOfLegends->value,
            'riot_match_id' => $this->matchIdFor(Game::LeagueOfLegends),
            'match_payload' => null,
            'discord_embed_payload' => null,
            'roast_text' => null,
            'discord_delivery_nonce' => null,
            'discord_message_id' => null,
            'status' => MatchNotificationStatus::Pending->value,
            'failure_reason' => null,
            'delivered_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'match_payload' => null,
            'discord_embed_payload' => null,
            'roast_text' => null,
            'discord_delivery_nonce' => null,
            'discord_message_id' => null,
            'status' => MatchNotificationStatus::Pending->value,
            'failure_reason' => null,
            'delivered_at' => null,
        ]);
    }

    public function sent(): static
    {
        return $this->state(function (array $attributes): array {
            $payload = $this->matchPayload($attributes);

            return [
                'match_payload' => $payload,
                'discord_embed_payload' => $this->discordEmbedPayload($payload),
                'roast_text' => fake()->sentence(),
                'discord_delivery_nonce' => (string) Str::uuid(),
                'discord_message_id' => $this->snowflake(),
                'status' => MatchNotificationStatus::Sent->value,
                'failure_reason' => null,
                'delivered_at' => now()->subMinutes(fake()->numberBetween(5, 240)),
            ];
        });
    }

    public function failed(): static
    {
        return $this->state(function (array $attributes): array {
            $payload = $this->matchPayload($attributes);

            return [
                'match_payload' => $payload,
                'discord_embed_payload' => $this->discordEmbedPayload($payload),
                'roast_text' => fake()->sentence(),
                'discord_delivery_nonce' => (string) Str::uuid(),
                'discord_message_id' => null,
                'status' => MatchNotificationStatus::Failed->value,
                'failure_reason' => 'Discord rejected the notification delivery.',
                'delivered_at' => null,
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function discordEmbedPayload(array $payload): array
    {
        return [
            'title' => $payload['title'],
            'description' => $payload['summary_line'],
            'color' => $payload['color'],
            'fields' => $payload['embed_fields'],
            'footer' => [
                'text' => 'Gamesentry',
            ],
            'timestamp' => $payload['finished_at'],
        ];
    }

    private function fallbackMatchId(): string
    {
        return 'MATCH_'.fake()->unique()->numerify('##########');
    }

    private function matchIdFor(Game $game): string
    {
        return match ($game) {
            Game::LeagueOfLegends => 'EUW1_'.fake()->unique()->numerify('##########'),
            Game::TeamfightTactics => 'TFTMATCH_'.fake()->unique()->numerify('##########'),
        };
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function matchPayload(array $attributes): array
    {
        $game = $this->gameFromAttributes($attributes);
        $result = fake()->randomElement(['victory', 'defeat']);
        $riotId = fake()->unique()->userName().'#'.strtoupper(fake()->bothify('??##'));

        return [
            'game' => $game->value,
            'match_id' => $attributes['riot_match_id'] ?? $this->fallbackMatchId(),
            'riot_id' => $riotId,
            'title' => match ($game) {
                Game::LeagueOfLegends => fake()->randomElement(['Ahri', 'Jinx', 'Lee Sin'])." - {$result}",
                Game::TeamfightTactics => 'TFT - #'.fake()->numberBetween(1, 8).'/8',
            },
            'summary_line' => fake()->sentence(),
            'color' => fake()->randomElement([0x22C55E, 0xF59E0B, 0xEF4444]),
            'finished_at' => now()->subMinutes(fake()->numberBetween(5, 240))->toIso8601String(),
            'duration_seconds' => fake()->numberBetween(900, 2400),
            'embed_fields' => [
                [
                    'name' => 'Result',
                    'value' => $result,
                    'inline' => true,
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function gameFromAttributes(array $attributes): Game
    {
        if (isset($attributes['game']) && is_string($attributes['game'])) {
            return Game::from($attributes['game']);
        }

        return Game::LeagueOfLegends;
    }

    private function snowflake(): string
    {
        return (string) fake()->unique()->numberBetween(
            100000000000000000,
            899999999999999999,
        );
    }
}
