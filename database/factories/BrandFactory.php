<?php

namespace Database\Factories;

use App\Models\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Brand>
 */
class BrandFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Brand::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->company();
        return [
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name),
            'description' => $this->faker->optional()->paragraph(),
            'logo_url' => $this->faker->imageUrl(200, 200, 'business'),
            'banner_url' => $this->faker->imageUrl(800, 200, 'business'),
            'website_url' => $this->faker->url(),
            'is_featured' => $this->faker->boolean(20),
            'is_active' => true,
        ];
    }
}
