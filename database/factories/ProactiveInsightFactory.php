<?php

namespace Database\Factories;

use App\Enums\InsightPriority;
use App\Enums\InsightType;
use App\Enums\LLMProvider;
use App\Enums\TriggerType;
use App\Models\BusinessEvent;
use App\Models\ProactiveInsight;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProactiveInsight>
 */
class ProactiveInsightFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'trigger_type' => fake()->randomElement(TriggerType::cases()),
            'trigger_context' => null,
            'insight_type' => fake()->randomElement(InsightType::cases()),
            'title' => fake()->sentence(4),
            'content' => fake()->paragraphs(2, true),
            'priority' => InsightPriority::Medium,
            'is_read' => false,
            'is_dismissed' => false,
            'related_event_id' => null,
            'provider' => LLMProvider::Claude,
            'model' => 'claude-3-5-sonnet-20241022',
            'tokens_used' => fake()->numberBetween(100, 2000),
        ];
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'trigger_type' => TriggerType::Scheduled,
            'trigger_context' => ['schedule' => 'daily'],
        ]);
    }

    public function threshold(): static
    {
        return $this->state(fn (array $attributes) => [
            'trigger_type' => TriggerType::Threshold,
            'trigger_context' => ['threshold' => 'runway', 'value' => 2.5],
        ]);
    }

    public function eventTriggered(): static
    {
        return $this->state(fn (array $attributes) => [
            'trigger_type' => TriggerType::Event,
            'related_event_id' => BusinessEvent::factory(),
        ]);
    }

    public function opportunity(): static
    {
        return $this->state(fn (array $attributes) => [
            'insight_type' => InsightType::Opportunity,
            'priority' => InsightPriority::Medium,
        ]);
    }

    public function warning(): static
    {
        return $this->state(fn (array $attributes) => [
            'insight_type' => InsightType::Warning,
            'priority' => InsightPriority::High,
        ]);
    }

    public function recommendation(): static
    {
        return $this->state(fn (array $attributes) => [
            'insight_type' => InsightType::Recommendation,
        ]);
    }

    public function analysis(): static
    {
        return $this->state(fn (array $attributes) => [
            'insight_type' => InsightType::Analysis,
        ]);
    }

    public function low(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => InsightPriority::Low,
        ]);
    }

    public function medium(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => InsightPriority::Medium,
        ]);
    }

    public function high(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => InsightPriority::High,
        ]);
    }

    public function urgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => InsightPriority::Urgent,
        ]);
    }

    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_read' => false,
        ]);
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
            'is_dismissed' => true,
        ]);
    }

    public function usingOpenAI(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => LLMProvider::OpenAI,
            'model' => 'gpt-4-turbo',
        ]);
    }
}
