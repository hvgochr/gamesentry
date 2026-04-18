<?php

namespace Database\Factories;

use App\Models\DiscordServer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DiscordServer>
 */
class DiscordServerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'discord_guild_id' => $this->snowflake(),
            'name' => fake()->unique()->company(),
            'icon' => strtolower(Str::random(32)),
            'discord_channel_id' => $this->snowflake(),
            'discord_channel_name' => fake()->unique()->slug(2),
            'bot_installed_at' => now()->subDays(fake()->numberBetween(1, 30)),
            'settings_synced_at' => now()->subHours(fake()->numberBetween(1, 72)),
        ];
    }

    public function withoutBot(): static
    {
        return $this->state(fn (array $attributes) => [
            'bot_installed_at' => null,
            'settings_synced_at' => null,
        ]);
    }

    public function staleSettings(): static
    {
        return $this->state(fn (array $attributes) => [
            'settings_synced_at' => now()->subDays(fake()->numberBetween(14, 45)),
        ]);
    }

    private function snowflake(): string
    {
        return (string) fake()->unique()->numberBetween(
            100000000000000000,
            899999999999999999,
        );
    }
}
