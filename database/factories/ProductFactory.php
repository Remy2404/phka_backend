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
            'brand_id' => \App\Models\Brand::factory(),
            'name' => ucwords($name),
            'slug' => \Illuminate\Support\Str::slug($name),
            'description' => $this->faker->paragraph(),
            'short_description' => $this->faker->sentence(),
            'base_price' => $this->faker->randomFloat(2, 10, 500),
            'original_price' => $this->faker->optional(0.3)->randomFloat(2, 5, 400),
            'is_on_sale' => $this->faker->boolean(30),
            'discount_percentage' => $this->faker->numberBetween(0, 50),
            'sku' => $this->faker->unique()->ean8(),
            'barcode' => $this->faker->ean13(),
            'stock_quantity' => $this->faker->numberBetween(0, 1000),
            'weight' => $this->faker->randomFloat(2, 0.1, 5),
            'dimensions' => json_encode([
                'length' => $this->faker->randomFloat(1, 5, 30),
                'width' => $this->faker->randomFloat(1, 5, 30),
                'height' => $this->faker->randomFloat(1, 5, 30),
                'unit' => 'cm'
            ]),
            'ingredients' => $this->faker->words(5, true),
            'how_to_use' => $this->faker->paragraph(),
            'benefits' => $this->faker->words(3, true),
            'warnings' => $this->faker->optional()->sentence(),
            'skin_types' => json_encode($this->faker->randomElements(['dry', 'oily', 'combination', 'normal', 'sensitive'], 2)),
            'skin_concerns' => json_encode($this->faker->randomElements(['acne', 'aging', 'dark_spots', 'wrinkles'], 2)),
            'is_vegan' => $this->faker->boolean(70),
            'is_cruelty_free' => $this->faker->boolean(80),
            'is_organic' => $this->faker->boolean(40),
            'is_paraben_free' => $this->faker->boolean(60),
            'is_sulfate_free' => $this->faker->boolean(50),
            'is_featured' => $this->faker->boolean(20),
            'is_new_arrival' => $this->faker->boolean(30),
            'is_best_seller' => $this->faker->boolean(25),
            'is_limited_edition' => $this->faker->boolean(10),
            'is_active' => true,
            'rating' => $this->faker->randomFloat(1, 1, 5),
            'review_count' => $this->faker->numberBetween(0, 100),
            'view_count' => $this->faker->numberBetween(0, 1000),
            'purchase_count' => $this->faker->numberBetween(0, 500),
            'published_at' => $this->faker->optional(0.9)->dateTime(), // 90% chance of being published
        ];
    }
}