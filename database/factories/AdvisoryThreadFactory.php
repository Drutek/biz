<?php

namespace Database\Factories;

use App\Models\AdvisoryThread;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdvisoryThread>
 */
class AdvisoryThreadFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(4),
            'context_snapshot' => [
                'monthly_income' => fake()->randomFloat(2, 5000, 50000),
                'monthly_expenses' => fake()->randomFloat(2, 2000, 20000),
                'runway_months' => fake()->numberBetween(3, 24),
                'created_at' => now()->toIso8601String(),
            ],
        ];
    }

    public function withEmptyContext(): static
    {
        return $this->state(fn (array $attributes) => [
            'context_snapshot' => null,
        ]);
    }
}
