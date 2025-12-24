<?php

namespace Database\Factories;

use App\Models\DailyStandup;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DailyStandup>
 */
class DailyStandupFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'standup_date' => now()->toDateString(),
            'financial_snapshot' => [
                'monthly_income' => fake()->randomFloat(2, 5000, 50000),
                'monthly_expenses' => fake()->randomFloat(2, 3000, 30000),
                'monthly_pipeline' => fake()->randomFloat(2, 1000, 20000),
                'monthly_net' => fake()->randomFloat(2, -5000, 20000),
                'runway_months' => fake()->optional(0.8)->randomFloat(1, 1, 24),
                'contracts_count' => fake()->numberBetween(1, 10),
                'pipeline_count' => fake()->numberBetween(0, 5),
            ],
            'alerts' => [],
            'ai_summary' => null,
            'ai_insights' => null,
            'events_count' => fake()->numberBetween(0, 10),
            'generated_at' => now(),
            'email_sent_at' => null,
            'viewed_at' => null,
        ];
    }

    public function withAlerts(): static
    {
        return $this->state(fn (array $attributes) => [
            'alerts' => [
                [
                    'type' => 'contract_ending',
                    'severity' => 'warning',
                    'message' => 'Contract ending in 14 days',
                    'contract_id' => 1,
                ],
                [
                    'type' => 'runway_low',
                    'severity' => 'danger',
                    'message' => 'Runway below 3 months',
                    'runway_months' => 2.5,
                ],
            ],
        ]);
    }

    public function withAiSummary(): static
    {
        return $this->state(fn (array $attributes) => [
            'ai_summary' => fake()->paragraph(3),
            'ai_insights' => [
                [
                    'type' => 'recommendation',
                    'content' => fake()->sentence(),
                ],
                [
                    'type' => 'opportunity',
                    'content' => fake()->sentence(),
                ],
            ],
        ]);
    }

    public function emailSent(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_sent_at' => now(),
        ]);
    }

    public function viewed(): static
    {
        return $this->state(fn (array $attributes) => [
            'viewed_at' => now(),
        ]);
    }

    public function forDate(\Carbon\Carbon $date): static
    {
        return $this->state(fn (array $attributes) => [
            'standup_date' => $date->toDateString(),
            'generated_at' => $date->copy()->setTime(6, 0, 0),
        ]);
    }

    public function yesterday(): static
    {
        return $this->forDate(now()->subDay());
    }
}
