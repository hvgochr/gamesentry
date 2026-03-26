<?php

namespace Database\Factories;

use App\Enums\PlatformRegion;
use App\Enums\RiotGame;
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
        $region = fake()->randomElement(PlatformRegion::cases());
        $game = fake()->randomElement(RiotGame::cases());

        return [
            'discord_server_id' => DiscordServer::factory(),
            'game' => $game->value,
            'riot_name' => fake()->unique()->lexify('Player????'),
            'riot_tagline' => strtoupper(fake()->lexify('???')),
            'region' => $region->value,
            'routing_region' => $region->routingRegion()->value,
            'discord_user_id' => fake()->unique()->numerify('##################'),
            'is_active' => true,
        ];
    }
}
