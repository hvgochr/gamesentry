<?php

namespace Database\Factories;

use App\Models\DailyUsageCounter;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DailyUsageCounter>
 */
class DailyUsageCounterFactory extends Factory
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
            'usage_date' => today(),
            'groq_calls' => fake()->numberBetween(0, 25),
        ];
    }
}
