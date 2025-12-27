<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductRevenueSnapshot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductRevenueSnapshot>
 */
class ProductRevenueSnapshotFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'mrr' => fake()->randomFloat(2, 0, 5000),
            'total_revenue' => fake()->randomFloat(2, 0, 50000),
            'subscriber_count' => fake()->numberBetween(0, 200),
            'units_sold' => fake()->numberBetween(0, 500),
            'recorded_at' => fake()->dateTimeBetween('-6 months', 'now'),
        ];
    }
}
