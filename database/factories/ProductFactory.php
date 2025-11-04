<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->words(3, true);
        return [
            'category_id' => Category::factory(),
            'name' => ucwords($name),
            'slug' => \Illuminate\Support\Str::slug($name),
            'description' => $this->faker->paragraph(),
            'short_description' => $this->faker->sentence(),
            'price' => $this->faker->randomFloat(2, 10, 500),
            'sale_price' => $this->faker->optional(0.3)->randomFloat(2, 5, 400), // 30% chance of sale price
            'cost_price' => $this->faker->randomFloat(2, 5, 300),
            'sku' => $this->faker->unique()->ean8(),
            'stock_quantity' => $this->faker->numberBetween(0, 1000),
            'stock_status' => $this->faker->randomElement(['in_stock', 'out_of_stock', 'on_backorder']),
            'weight' => $this->faker->randomFloat(2, 0.1, 5),
            'dimensions' => json_encode([
                'length' => $this->faker->randomFloat(1, 5, 30),
                'width' => $this->faker->randomFloat(1, 5, 30),
                'height' => $this->faker->randomFloat(1, 5, 30),
            ]),
            'brand' => $this->faker->company(),
            'tags' => json_encode($this->faker->words(3)),
            'images' => json_encode([$this->faker->imageUrl(640, 480, 'fashion')]),
            'featured_image' => $this->faker->imageUrl(640, 480, 'fashion'),
            'is_active' => true,
            'is_featured' => $this->faker->boolean(20), // 20% chance of being featured
            'rating' => $this->faker->randomFloat(1, 1, 5),
            'review_count' => $this->faker->numberBetween(0, 100),
        ];
    }
}