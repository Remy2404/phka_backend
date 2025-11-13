<?php

namespace Database\Factories;

use App\Models\ProductReview;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductReview>
 */
class ProductReviewFactory extends Factory
{
    protected $model = ProductReview::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'user_id' => User::factory(),
            'rating' => fake()->numberBetween(1, 5),
            'title' => fake()->sentence(),
            'content' => fake()->paragraph(),
            'is_verified_purchase' => fake()->boolean(80), // 80% are verified purchases
            'is_approved' => true,
            'helpful_count' => fake()->numberBetween(0, 50),
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified_purchase' => true,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_approved' => false,
        ]);
    }
}