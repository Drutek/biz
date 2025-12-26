<?php

namespace Database\Factories;

use App\Models\ProactiveInsight;
use App\Models\TaskSuggestion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskSuggestion>
 */
class TaskSuggestionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'proactive_insight_id' => ProactiveInsight::factory(),
            'suggestion_hash' => TaskSuggestion::generateHash(fake()->sentence(4)),
            'was_accepted' => false,
            'was_rejected' => false,
            'suggested_at' => now(),
        ];
    }

    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'was_accepted' => true,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'was_rejected' => true,
        ]);
    }
}
