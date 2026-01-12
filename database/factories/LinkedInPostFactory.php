<?php

namespace Database\Factories;

use App\Enums\LinkedInPostType;
use App\Enums\LinkedInTone;
use App\Enums\LLMProvider;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LinkedInPost>
 */
class LinkedInPostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'news_item_id' => null,
            'post_type' => fake()->randomElement(LinkedInPostType::cases()),
            'tone' => fake()->randomElement(LinkedInTone::cases()),
            'title' => fake()->sentence(),
            'content' => fake()->paragraphs(3, true),
            'hashtags' => fake()->words(5),
            'call_to_action' => fake()->optional()->sentence(),
            'is_used' => false,
            'is_dismissed' => false,
            'provider' => LLMProvider::Claude,
            'model' => 'claude-sonnet-4-20250514',
            'tokens_used' => fake()->numberBetween(100, 500),
            'generated_at' => now(),
        ];
    }

    public function newsCommentary(): static
    {
        return $this->state(fn (array $attributes) => [
            'post_type' => LinkedInPostType::NewsCommentary,
        ]);
    }

    public function thoughtLeadership(): static
    {
        return $this->state(fn (array $attributes) => [
            'post_type' => LinkedInPostType::ThoughtLeadership,
        ]);
    }

    public function industryInsight(): static
    {
        return $this->state(fn (array $attributes) => [
            'post_type' => LinkedInPostType::IndustryInsight,
        ]);
    }

    public function companyUpdate(): static
    {
        return $this->state(fn (array $attributes) => [
            'post_type' => LinkedInPostType::CompanyUpdate,
        ]);
    }

    public function professional(): static
    {
        return $this->state(fn (array $attributes) => [
            'tone' => LinkedInTone::Professional,
        ]);
    }

    public function conversational(): static
    {
        return $this->state(fn (array $attributes) => [
            'tone' => LinkedInTone::Conversational,
        ]);
    }

    public function used(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_used' => true,
        ]);
    }

    public function dismissed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_dismissed' => true,
        ]);
    }
}
