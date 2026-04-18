<?php

namespace Database\Factories;

use App\Enums\Game;
use App\Models\DiscordServer;
use App\Models\WatchedPlayer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WatchedPlayer>
 */
class WatchedPlayerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'discord_server_id' => DiscordServer::factory(),
            'game' => Game::LeagueOfLegends->value,
            'routing_region' => 'europe',
            'game_name' => fake()->unique()->userName(),
            'tag_line' => strtoupper(fake()->bothify('??##')),
            'riot_puuid' => (string) Str::uuid(),
            'discord_user_id' => $this->snowflake(),
            'last_seen_match_id' => $this->matchIdFor(Game::LeagueOfLegends),
            'last_polled_at' => now()->subMinutes(fake()->numberBetween(10, 180)),
            'next_poll_at' => now()->addSeconds(fake()->numberBetween(60, 900)),
            'poll_interval_seconds' => fake()->randomElement([300, 600, 900]),
            'is_active' => true,
        ];
    }

    public function leagueOfLegends(): static
    {
        return $this->state(fn (array $attributes) => [
            'game' => Game::LeagueOfLegends->value,
            'routing_region' => 'europe',
            'last_seen_match_id' => $this->matchIdFor(Game::LeagueOfLegends),
        ]);
    }

    public function teamfightTactics(): static
    {
        return $this->state(fn (array $attributes) => [
            'game' => Game::TeamfightTactics->value,
            'routing_region' => 'europe',
            'last_seen_match_id' => $this->matchIdFor(Game::TeamfightTactics),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'next_poll_at' => null,
        ]);
    }

    public function dueForPolling(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_polled_at' => now()->subMinutes(fake()->numberBetween(10, 120)),
            'next_poll_at' => now()->subMinutes(fake()->numberBetween(1, 15)),
        ]);
    }

    public function neverPolled(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_polled_at' => null,
            'next_poll_at' => now()->addMinutes(5),
        ]);
    }

    private function matchIdFor(Game $game): string
    {
        return match ($game) {
            Game::LeagueOfLegends => 'EUW1_'.fake()->unique()->numerify('##########'),
            Game::TeamfightTactics => 'TFTMATCH_'.fake()->unique()->numerify('##########'),
        };
    }

    private function snowflake(): string
    {
        return (string) fake()->unique()->numberBetween(
            100000000000000000,
            899999999999999999,
        );
    }
}
