<?php

namespace Database\Factories;

use App\Models\DiscordServer;
use App\Models\TrackedPlayer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TrackedPlayer>
 */
class TrackedPlayerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $region = fake()->randomElement(['euw', 'na', 'kr', 'oce']);

        return [
            'discord_server_id' => DiscordServer::factory(),
            'game' => fake()->randomElement(array_keys(TrackedPlayer::GAME_LABELS)),
            'riot_name' => fake()->unique()->lexify('Player????'),
            'riot_tagline' => strtoupper(fake()->lexify('???')),
            'region' => $region,
            'routing_region' => TrackedPlayer::routingRegionFor($region),
            'discord_user_id' => fake()->unique()->numerify('##################'),
            'is_active' => true,
        ];
    }
}
