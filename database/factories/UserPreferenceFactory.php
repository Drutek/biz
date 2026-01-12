<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserPreference>
 */
class UserPreferenceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'standup_email_enabled' => true,
            'standup_email_time' => '08:00',
            'standup_email_timezone' => 'UTC',
            'in_app_notifications_enabled' => true,
            'proactive_insights_enabled' => true,
            'insight_frequency' => 'weekly',
            'runway_alert_threshold' => 3,
        ];
    }

    public function emailDisabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'standup_email_enabled' => false,
        ]);
    }

    public function notificationsDisabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'in_app_notifications_enabled' => false,
        ]);
    }

    public function insightsDisabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'proactive_insights_enabled' => false,
        ]);
    }

    public function dailyInsights(): static
    {
        return $this->state(fn (array $attributes) => [
            'insight_frequency' => 'daily',
        ]);
    }

    public function eventOnlyInsights(): static
    {
        return $this->state(fn (array $attributes) => [
            'insight_frequency' => 'event_only',
        ]);
    }

    public function withTimezone(string $timezone): static
    {
        return $this->state(fn (array $attributes) => [
            'standup_email_timezone' => $timezone,
        ]);
    }

    public function withEmailTime(string $time): static
    {
        return $this->state(fn (array $attributes) => [
            'standup_email_time' => $time,
        ]);
    }

    public function withRunwayThreshold(int $months): static
    {
        return $this->state(fn (array $attributes) => [
            'runway_alert_threshold' => $months,
        ]);
    }
}
