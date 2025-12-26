<?php

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Enums\TaskSource;
use App\Enums\TaskStatus;
use App\Models\DailyStandup;
use App\Models\ProactiveInsight;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'proactive_insight_id' => null,
            'daily_standup_id' => null,
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'status' => TaskStatus::Suggested,
            'priority' => TaskPriority::Medium,
            'source' => TaskSource::Ai,
            'due_date' => fake()->optional(0.7)->dateTimeBetween('now', '+2 weeks'),
            'suggested_at' => now(),
            'accepted_at' => null,
            'started_at' => null,
            'completed_at' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
            'completion_notes' => null,
            'metadata' => null,
        ];
    }

    public function fromInsight(): static
    {
        return $this->state(fn (array $attributes) => [
            'proactive_insight_id' => ProactiveInsight::factory(),
            'source' => TaskSource::Ai,
        ]);
    }

    public function fromStandup(): static
    {
        return $this->state(fn (array $attributes) => [
            'daily_standup_id' => DailyStandup::factory(),
            'source' => TaskSource::Standup,
        ]);
    }

    public function manual(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => TaskSource::Manual,
        ]);
    }

    public function suggested(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TaskStatus::Suggested,
        ]);
    }

    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TaskStatus::Accepted,
            'accepted_at' => now(),
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TaskStatus::InProgress,
            'accepted_at' => now()->subHours(2),
            'started_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TaskStatus::Completed,
            'accepted_at' => now()->subDays(2),
            'started_at' => now()->subDay(),
            'completed_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TaskStatus::Rejected,
            'rejected_at' => now(),
            'rejection_reason' => fake()->sentence(),
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TaskStatus::Accepted,
            'accepted_at' => now()->subWeek(),
            'due_date' => now()->subDays(2),
        ]);
    }

    public function dueToday(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => now()->toDateString(),
        ]);
    }

    public function dueTomorrow(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => now()->addDay()->toDateString(),
        ]);
    }

    public function low(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => TaskPriority::Low,
        ]);
    }

    public function medium(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => TaskPriority::Medium,
        ]);
    }

    public function high(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => TaskPriority::High,
        ]);
    }

    public function urgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => TaskPriority::Urgent,
        ]);
    }
}
