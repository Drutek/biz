<?php

namespace Database\Factories;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Setting>
 */
class SettingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => fake()->unique()->word(),
            'value' => fake()->sentence(),
        ];
    }

    public function companyName(string $name = null): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => Setting::KEY_COMPANY_NAME,
            'value' => $name ?? fake()->company(),
        ]);
    }

    public function preferredProvider(string $provider = 'claude'): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => Setting::KEY_PREFERRED_LLM_PROVIDER,
            'value' => $provider,
        ]);
    }
}
