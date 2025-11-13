<?php

namespace Database\Factories;

use App\Models\TutorialVideo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TutorialVideo>
 */
class TutorialVideoFactory extends Factory
{
    protected $model = TutorialVideo::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'description' => fake()->paragraphs(3, true),
            'video_url' => fake()->url() . '/video.mp4',
            'duration' => fake()->numberBetween(120, 1800), // 2-30 minutes in seconds
            'category' => fake()->randomElement(['skincare', 'makeup', 'haircare', 'nails', 'fragrance']),
            'tags' => fake()->randomElements(['tutorial', 'step-by-step', 'beginner', 'advanced', 'tips'], 3),
            'view_count' => fake()->numberBetween(0, 10000),
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