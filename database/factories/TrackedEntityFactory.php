<?php

namespace Database\Factories;

use App\Enums\EntityType;
use App\Models\TrackedEntity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrackedEntity>
 */
class TrackedEntityFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $entityType = fake()->randomElement(EntityType::cases());
        $name = match ($entityType) {
            EntityType::Company => fake()->company(),
            EntityType::Industry => fake()->randomElement(['Healthcare', 'Technology', 'Finance', 'Manufacturing']),
            EntityType::Topic => fake()->randomElement(['AI consulting', 'Digital transformation', 'Cloud migration']),
            EntityType::Competitor => fake()->company(),
        };

        return [
            'name' => $name,
            'entity_type' => $entityType,
            'search_query' => $name.' news',
            'is_active' => true,
        ];
    }

    public function company(): static
    {
        return $this->state(fn (array $attributes) => [
            'entity_type' => EntityType::Company,
            'name' => fake()->company(),
        ]);
    }

    public function industry(): static
    {
        return $this->state(fn (array $attributes) => [
            'entity_type' => EntityType::Industry,
        ]);
    }

    public function topic(): static
    {
        return $this->state(fn (array $attributes) => [
            'entity_type' => EntityType::Topic,
        ]);
    }

    public function competitor(): static
    {
        return $this->state(fn (array $attributes) => [
            'entity_type' => EntityType::Competitor,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
