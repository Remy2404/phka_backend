<?php

namespace Tests\Feature\Api;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_products_list()
    {
        // Create test data
        $category = Category::factory()->create();
        Product::factory()->count(5)->create(['category_id' => $category->id]);

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                ])
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'data',
                        'current_page',
                        'per_page',
                        'total',
                    ],
                ]);
    }

    public function test_can_get_featured_products()
    {
        // Create 3 featured products explicitly
        Product::create([
            'category_id' => Category::factory()->create()->id,
            'name' => 'Featured Product 1',
            'slug' => 'featured-product-1',
            'description' => 'A featured product',
            'base_price' => 29.99,
            'sku' => 'FP001',
            'stock_quantity' => 100,
            'stock_status' => 'in_stock',
            'is_active' => true,
            'is_featured' => true,
            'rating' => 4.5,
            'review_count' => 10,
        ]);

        Product::create([
            'category_id' => Category::factory()->create()->id,
            'name' => 'Featured Product 2',
            'slug' => 'featured-product-2',
            'description' => 'Another featured product',
            'base_price' => 39.99,
            'sku' => 'FP002',
            'stock_quantity' => 100,
            'stock_status' => 'in_stock',
            'is_active' => true,
            'is_featured' => true,
            'rating' => 4.5,
            'review_count' => 10,
        ]);

        Product::create([
            'category_id' => Category::factory()->create()->id,
            'name' => 'Featured Product 3',
            'slug' => 'featured-product-3',
            'description' => 'Third featured product',
            'base_price' => 49.99,
            'sku' => 'FP003',
            'stock_quantity' => 100,
            'stock_status' => 'in_stock',
            'is_active' => true,
            'is_featured' => true,
            'rating' => 4.5,
            'review_count' => 10,
        ]);

        // Create some non-featured products to ensure filtering works
        Product::factory()->count(2)->create(['is_featured' => false]);

        $response = $this->getJson('/api/products/featured');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                ])
                ->assertJsonCount(3, 'data');
    }

    public function test_can_get_single_product()
    {
        $product = Product::factory()->create();

        $response = $this->getJson('/api/products/' . $product->id);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'id' => $product->id,
                        'name' => $product->name,
                    ],
                ]);
    }

    public function test_can_search_products()
    {
        // Create products with very specific names to avoid factory interference
        Product::create([
            'category_id' => Category::factory()->create()->id,
            'name' => 'Amazing Face Cream',
            'slug' => 'amazing-face-cream',
            'description' => 'A great face cream',
            'base_price' => 29.99,
            'sku' => 'AFC001',
            'stock_quantity' => 100,
            'stock_status' => 'in_stock',
            'is_active' => true,
            'is_featured' => false,
            'rating' => 4.5,
            'review_count' => 10,
        ]);

        Product::create([
            'category_id' => Category::factory()->create()->id,
            'name' => 'Body Lotion',
            'slug' => 'body-lotion',
            'description' => 'A nice body lotion',
            'base_price' => 19.99,
            'sku' => 'BL001',
            'stock_quantity' => 100,
            'stock_status' => 'in_stock',
            'is_active' => true,
            'is_featured' => false,
            'rating' => 4.0,
            'review_count' => 5,
        ]);

        $response = $this->getJson('/api/products/search?q=face');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                ])
                ->assertJsonCount(1, 'data');
    }

    public function test_can_get_products_by_category()
    {
        $category = Category::factory()->create();
        Product::factory()->count(3)->create(['category_id' => $category->id]);

        $response = $this->getJson('/api/categories/' . $category->id . '/products');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'category' => [
                            'id' => $category->id,
                            'name' => $category->name,
                        ],
                    ],
                ]);
    }
}