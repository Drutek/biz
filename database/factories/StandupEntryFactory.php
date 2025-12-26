<?php

namespace Database\Factories;

use App\Models\DailyStandup;
use App\Models\StandupEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StandupEntry>
 */
class StandupEntryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'daily_standup_id' => DailyStandup::factory(),
            'yesterday_accomplished' => fake()->paragraph(),
            'today_planned' => fake()->paragraph(),
            'blockers' => null,
            'ai_follow_up_questions' => null,
            'ai_follow_up_responses' => null,
            'ai_analysis' => null,
            'submitted_at' => now(),
        ];
    }

    public function withBlockers(): static
    {
        return $this->state(fn (array $attributes) => [
            'blockers' => fake()->sentence(),
        ]);
    }

    public function withFollowUp(): static
    {
        return $this->state(fn (array $attributes) => [
            'ai_follow_up_questions' => [
                'Can you elaborate on the client meeting mentioned?',
                'What specific blockers are you facing with the API integration?',
            ],
            'ai_follow_up_responses' => [
                'The client wants to discuss new feature requirements.',
                'Waiting on third-party documentation.',
            ],
        ]);
    }

    public function withAnalysis(): static
    {
        return $this->state(fn (array $attributes) => [
            'ai_analysis' => fake()->paragraphs(2, true),
        ]);
    }

    public function complete(): static
    {
        return $this->withFollowUp()->withAnalysis();
    }

    public function notSubmitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'submitted_at' => null,
        ]);
    }
}
