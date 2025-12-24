<?php

namespace Database\Factories;

use App\Enums\EventCategory;
use App\Enums\EventSignificance;
use App\Enums\EventType;
use App\Models\BusinessEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BusinessEvent>
 */
class BusinessEventFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'event_type' => fake()->randomElement(EventType::cases()),
            'category' => fake()->randomElement(EventCategory::cases()),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'metadata' => null,
            'significance' => EventSignificance::Medium,
            'eventable_type' => null,
            'eventable_id' => null,
            'occurred_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }

    public function financial(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => EventCategory::Financial,
            'event_type' => fake()->randomElement([
                EventType::ContractSigned,
                EventType::ContractRenewed,
                EventType::ContractExpired,
                EventType::ExpenseChange,
                EventType::RunwayThreshold,
            ]),
        ]);
    }

    public function market(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => EventCategory::Market,
            'event_type' => EventType::NewsAlert,
        ]);
    }

    public function advisory(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => EventCategory::Advisory,
            'event_type' => EventType::AiInsight,
        ]);
    }

    public function milestone(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => EventCategory::Milestone,
            'event_type' => EventType::Manual,
        ]);
    }

    public function contractSigned(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => EventType::ContractSigned,
            'category' => EventCategory::Financial,
            'significance' => EventSignificance::High,
        ]);
    }

    public function contractEnding(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => EventType::ContractEnding,
            'category' => EventCategory::Financial,
            'significance' => EventSignificance::High,
        ]);
    }

    public function runwayAlert(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => EventType::RunwayThreshold,
            'category' => EventCategory::Financial,
            'significance' => EventSignificance::Critical,
            'title' => 'Runway below threshold',
        ]);
    }

    public function low(): static
    {
        return $this->state(fn (array $attributes) => [
            'significance' => EventSignificance::Low,
        ]);
    }

    public function medium(): static
    {
        return $this->state(fn (array $attributes) => [
            'significance' => EventSignificance::Medium,
        ]);
    }

    public function high(): static
    {
        return $this->state(fn (array $attributes) => [
            'significance' => EventSignificance::High,
        ]);
    }

    public function critical(): static
    {
        return $this->state(fn (array $attributes) => [
            'significance' => EventSignificance::Critical,
        ]);
    }

    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'occurred_at' => fake()->dateTimeBetween('-24 hours', 'now'),
        ]);
    }

    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'occurred_at' => now(),
        ]);
    }
}
