<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DiscordServer>
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
            'name' => fake()->company().' HQ',
            'discord_guild_id' => fake()->unique()->numerify('##################'),
            'alert_channel_name' => fake()->randomElement(['match-feed', 'riot-alerts', 'scoreboard']),
            'alert_channel_id' => fake()->unique()->numerify('##################'),
            'roast_enabled' => true,
        ];
    }
}
