<?php

namespace Database\Factories;

use App\Enums\ExpenseFrequency;
use App\Models\Expense;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    private const CATEGORIES = ['software', 'professional', 'office', 'tax', 'marketing', 'travel', 'equipment'];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-6 months', 'now');

        return [
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'amount' => fake()->randomFloat(2, 50, 5000),
            'frequency' => fake()->randomElement(ExpenseFrequency::cases()),
            'category' => fake()->randomElement(self::CATEGORIES),
            'start_date' => $startDate,
            'end_date' => fake()->optional(0.3)->dateTimeBetween($startDate, '+2 years'),
            'is_active' => true,
        ];
    }

    public function monthly(float $amount = null): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => ExpenseFrequency::Monthly,
            'amount' => $amount ?? $attributes['amount'],
        ]);
    }

    public function quarterly(float $amount = null): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => ExpenseFrequency::Quarterly,
            'amount' => $amount ?? $attributes['amount'],
        ]);
    }

    public function annual(float $amount = null): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => ExpenseFrequency::Annual,
            'amount' => $amount ?? $attributes['amount'],
        ]);
    }

    public function oneTime(float $amount = null): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => ExpenseFrequency::OneTime,
            'amount' => $amount ?? $attributes['amount'],
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'start_date' => now()->subMonth(),
            'end_date' => null,
        ]);
    }

    public function inCategory(string $category): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => $category,
        ]);
    }
}
