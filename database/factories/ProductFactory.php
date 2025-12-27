<?php

namespace Database\Factories;

use App\Enums\BillingFrequency;
use App\Enums\PricingModel;
use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->paragraph(),
            'product_type' => fake()->randomElement(ProductType::cases()),
            'status' => ProductStatus::Idea,
            'price' => fake()->randomFloat(2, 9.99, 499.99),
            'pricing_model' => PricingModel::OneTime,
            'billing_frequency' => null,
            'mrr' => 0,
            'total_revenue' => 0,
            'subscriber_count' => 0,
            'units_sold' => 0,
            'hours_invested' => 0,
            'monthly_maintenance_hours' => 0,
            'launched_at' => null,
            'target_launch_date' => fake()->optional()->dateTimeBetween('now', '+1 year'),
            'url' => fake()->optional()->url(),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function launched(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProductStatus::Launched,
            'launched_at' => fake()->dateTimeBetween('-1 year', '-1 day'),
            'total_revenue' => fake()->randomFloat(2, 100, 50000),
            'units_sold' => fake()->numberBetween(10, 500),
            'hours_invested' => fake()->randomFloat(2, 20, 500),
            'monthly_maintenance_hours' => fake()->randomFloat(2, 1, 20),
        ]);
    }

    public function inDevelopment(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProductStatus::InDevelopment,
            'hours_invested' => fake()->randomFloat(2, 10, 200),
            'target_launch_date' => fake()->dateTimeBetween('+1 month', '+6 months'),
        ]);
    }

    public function subscription(): static
    {
        return $this->state(fn (array $attributes) => [
            'pricing_model' => PricingModel::Subscription,
            'billing_frequency' => BillingFrequency::Monthly,
            'mrr' => fake()->randomFloat(2, 100, 5000),
            'subscriber_count' => fake()->numberBetween(5, 200),
        ]);
    }

    public function book(): static
    {
        return $this->state(fn (array $attributes) => [
            'product_type' => ProductType::Book,
            'pricing_model' => PricingModel::OneTime,
            'price' => fake()->randomFloat(2, 9.99, 49.99),
        ]);
    }

    public function saasApp(): static
    {
        return $this->state(fn (array $attributes) => [
            'product_type' => ProductType::SaasApp,
            'pricing_model' => PricingModel::Subscription,
            'billing_frequency' => BillingFrequency::Monthly,
        ]);
    }

    public function course(): static
    {
        return $this->state(fn (array $attributes) => [
            'product_type' => ProductType::Course,
            'pricing_model' => PricingModel::OneTime,
            'price' => fake()->randomFloat(2, 49, 499),
        ]);
    }

    public function retired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProductStatus::Retired,
        ]);
    }
}
