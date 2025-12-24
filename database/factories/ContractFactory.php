<?php

namespace Database\Factories;

use App\Enums\BillingFrequency;
use App\Enums\ContractStatus;
use App\Models\Contract;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contract>
 */
class ContractFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-6 months', '+1 month');

        return [
            'name' => fake()->company().' - '.fake()->bs(),
            'description' => fake()->optional()->paragraph(),
            'value' => fake()->randomFloat(2, 1000, 50000),
            'billing_frequency' => fake()->randomElement(BillingFrequency::cases()),
            'start_date' => $startDate,
            'end_date' => fake()->optional(0.7)->dateTimeBetween($startDate, '+2 years'),
            'probability' => 100,
            'status' => ContractStatus::Confirmed,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ContractStatus::Confirmed,
            'probability' => 100,
        ]);
    }

    public function pipeline(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ContractStatus::Pipeline,
            'probability' => fake()->numberBetween(10, 90),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ContractStatus::Completed,
            'end_date' => fake()->dateTimeBetween('-6 months', '-1 day'),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ContractStatus::Cancelled,
        ]);
    }

    public function monthly(float $value = null): static
    {
        return $this->state(fn (array $attributes) => [
            'billing_frequency' => BillingFrequency::Monthly,
            'value' => $value ?? $attributes['value'],
        ]);
    }

    public function quarterly(float $value = null): static
    {
        return $this->state(fn (array $attributes) => [
            'billing_frequency' => BillingFrequency::Quarterly,
            'value' => $value ?? $attributes['value'],
        ]);
    }

    public function annual(float $value = null): static
    {
        return $this->state(fn (array $attributes) => [
            'billing_frequency' => BillingFrequency::Annual,
            'value' => $value ?? $attributes['value'],
        ]);
    }

    public function oneTime(float $value = null): static
    {
        return $this->state(fn (array $attributes) => [
            'billing_frequency' => BillingFrequency::OneTime,
            'value' => $value ?? $attributes['value'],
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => now()->subMonth(),
            'end_date' => now()->addYear(),
        ]);
    }
}
