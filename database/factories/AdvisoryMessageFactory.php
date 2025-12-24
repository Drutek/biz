<?php

namespace Database\Factories;

use App\Enums\LLMProvider;
use App\Enums\MessageRole;
use App\Models\AdvisoryMessage;
use App\Models\AdvisoryThread;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdvisoryMessage>
 */
class AdvisoryMessageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'advisory_thread_id' => AdvisoryThread::factory(),
            'role' => MessageRole::User,
            'content' => fake()->paragraph(),
            'provider' => null,
            'model' => null,
            'tokens_used' => null,
        ];
    }

    public function fromUser(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => MessageRole::User,
            'provider' => null,
            'model' => null,
            'tokens_used' => null,
        ]);
    }

    public function fromAssistant(LLMProvider $provider = LLMProvider::Claude): static
    {
        $model = $provider === LLMProvider::Claude
            ? 'claude-sonnet-4-20250514'
            : 'gpt-4o';

        return $this->state(fn (array $attributes) => [
            'role' => MessageRole::Assistant,
            'provider' => $provider,
            'model' => $model,
            'tokens_used' => fake()->numberBetween(100, 2000),
        ]);
    }

    public function inThread(AdvisoryThread $thread): static
    {
        return $this->state(fn (array $attributes) => [
            'advisory_thread_id' => $thread->id,
        ]);
    }
}
