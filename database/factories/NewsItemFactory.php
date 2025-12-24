<?php

namespace Database\Factories;

use App\Models\NewsItem;
use App\Models\TrackedEntity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NewsItem>
 */
class NewsItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tracked_entity_id' => TrackedEntity::factory(),
            'title' => fake()->sentence(),
            'snippet' => fake()->paragraph(),
            'url' => fake()->url(),
            'source' => fake()->randomElement(['Reuters', 'Bloomberg', 'TechCrunch', 'BBC', 'Financial Times']),
            'published_at' => fake()->dateTimeBetween('-7 days', 'now'),
            'fetched_at' => now(),
            'is_read' => false,
            'is_relevant' => true,
        ];
    }

    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_read' => true,
        ]);
    }

    public function dismissed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_relevant' => false,
        ]);
    }

    public function forEntity(TrackedEntity $entity): static
    {
        return $this->state(fn (array $attributes) => [
            'tracked_entity_id' => $entity->id,
        ]);
    }

    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'published_at' => fake()->dateTimeBetween('-2 days', 'now'),
            'fetched_at' => now(),
        ]);
    }

    public function old(): static
    {
        return $this->state(fn (array $attributes) => [
            'published_at' => fake()->dateTimeBetween('-30 days', '-14 days'),
            'fetched_at' => now()->subDays(14),
        ]);
    }
}
