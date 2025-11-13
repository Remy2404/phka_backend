<?php

namespace Database\Factories;

use App\Models\BeautyTip;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BeautyTip>
 */
class BeautyTipFactory extends Factory
{
    protected $model = BeautyTip::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'content' => fake()->paragraphs(3, true),
            'category' => fake()->randomElement(['skincare', 'makeup', 'haircare', 'nails', 'fragrance']),
            'tags' => fake()->randomElements(['moisturizing', 'anti-aging', 'acne', 'dry-skin', 'oily-skin'], 3),
            'author_id' => User::factory(),
            'is_featured' => fake()->boolean(20), // 20% chance of being featured
            'published_at' => fake()->optional(0.8)->dateTimeBetween('-6 months', 'now'),
        ];
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'published_at' => fake()->dateTimeBetween('-6 months', 'now'),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'published_at' => null,
        ]);
    }
}