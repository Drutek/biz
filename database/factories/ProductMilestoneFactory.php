<?php

namespace Database\Factories;

use App\Enums\MilestoneStatus;
use App\Models\Product;
use App\Models\ProductMilestone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductMilestone>
 */
class ProductMilestoneFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'status' => MilestoneStatus::NotStarted,
            'target_date' => fake()->optional()->dateTimeBetween('now', '+3 months'),
            'completed_at' => null,
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MilestoneStatus::Completed,
            'completed_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MilestoneStatus::InProgress,
        ]);
    }

    public function blocked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MilestoneStatus::Blocked,
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MilestoneStatus::InProgress,
            'target_date' => fake()->dateTimeBetween('-1 month', '-1 day'),
        ]);
    }
}
