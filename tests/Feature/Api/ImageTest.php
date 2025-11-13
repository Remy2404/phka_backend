<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\Category;
use App\Models\BeautyTip;
use App\Models\TutorialVideo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageTest extends TestCase
{
    use RefreshDatabase;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user
        $this->user = User::factory()->create();

        // Fake the storage disk for testing
        Storage::fake('public');
    }

    public function test_can_upload_product_image()
    {
        $category = Category::factory()->create();
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Test Product',
            'slug' => 'test-product',
            'description' => 'A test product',
            'base_price' => 29.99,
            'sku' => 'TEST001',
            'stock_quantity' => 100,
            'stock_status' => 'in_stock',
            'is_active' => true,
        ]);
        
        $file = UploadedFile::fake()->image('product.jpg');

        $response = $this->actingAs($this->user, 'sanctum')
                        ->postJson('/api/images/products/' . $product->id, [
                            'images' => [$file],
                        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                ]);

        // Assert the file was stored
        Storage::disk('public')->assertExists('products/' . basename($response->json('data.0.image_url')));
    }

    public function test_can_upload_review_image()
    {
        $category = Category::factory()->create();
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Test Product',
            'slug' => 'test-product',
            'description' => 'A test product',
            'base_price' => 29.99,
            'sku' => 'TEST001',
            'stock_quantity' => 100,
            'stock_status' => 'in_stock',
            'is_active' => true,
        ]);
        
        $review = \App\Models\ProductReview::create([
            'product_id' => $product->id,
            'user_id' => $this->user->id,
            'rating' => 5,
            'title' => 'Great product',
            'content' => 'This is a great product',
            'is_verified' => true,
            'is_featured' => false,
        ]);
        
        $file = UploadedFile::fake()->image('review.jpg');

        $response = $this->actingAs($this->user, 'sanctum')
                        ->postJson('/api/images/reviews/' . $review->id, [
                            'images' => [$file],
                        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                ]);

        // Assert the file was stored
        Storage::disk('public')->assertExists('reviews/' . basename($response->json('data.0.image_url')));
    }

    public function test_can_upload_category_image()
    {
        $category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
            'description' => 'A test category',
            'is_active' => true,
        ]);
        
        $file = UploadedFile::fake()->image('category.jpg');

        $response = $this->actingAs($this->user, 'sanctum')
                        ->postJson('/api/images/categories/' . $category->id, [
                            'image' => $file,
                        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                ]);

        // Assert the file was stored
        Storage::disk('public')->assertExists('categories/' . basename($response->json('data.image_url')));
    }

    public function test_can_upload_avatar()
    {
        $file = UploadedFile::fake()->image('avatar.jpg');

        $response = $this->actingAs($this->user, 'sanctum')
                        ->postJson('/api/user/avatar', [
                            'avatar' => $file,
                        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                ])
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id',
                        'name',
                        'email',
                        'avatar',
                        'avatar_url',
                    ],
                ]);

        // Assert the file was stored
        Storage::disk('public')->assertExists('avatars/' . $this->user->fresh()->avatar);
    }

    public function test_can_upload_beauty_tip_image()
    {
        $beautyTip = BeautyTip::factory()->create();
        
        $file = UploadedFile::fake()->image('beauty-tip.jpg');

        $response = $this->actingAs($this->user, 'sanctum')
                        ->postJson('/api/images/beauty-tips/' . $beautyTip->id, [
                            'image' => $file,
                        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                ])
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'image_url',
                    ],
                ]);

        // Assert the file was stored
        Storage::disk('public')->assertExists('beauty-tips/' . basename($response->json('data.image_url')));
    }

    public function test_can_upload_tutorial_image()
    {
        $tutorial = TutorialVideo::factory()->create();
        
        $file = UploadedFile::fake()->image('tutorial.jpg');

        $response = $this->actingAs($this->user, 'sanctum')
                        ->postJson('/api/images/tutorials/' . $tutorial->id, [
                            'thumbnail' => $file,
                        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                ])
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'thumbnail_url',
                    ],
                ]);

        // Assert the file was stored
        Storage::disk('public')->assertExists('tutorials/' . basename($response->json('data.thumbnail_url')));
    }

    public function test_requires_authentication_for_image_upload()
    {
        $product = Product::factory()->create();
        
        $file = UploadedFile::fake()->image('product.jpg');

        $response = $this->postJson('/api/images/products/' . $product->id, [
            'images' => [$file],
        ]);

        $response->assertStatus(401);
    }

    public function test_validates_image_file_type()
    {
        $product = Product::factory()->create();
        
        $file = UploadedFile::fake()->create('document.pdf', 1000, 'application/pdf');

        $response = $this->actingAs($this->user, 'sanctum')
                        ->postJson('/api/images/products/' . $product->id, [
                            'images' => [$file],
                        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['images.0']);
    }

    public function test_validates_image_file_size()
    {
        $product = Product::factory()->create();
        
        // Create a file larger than 5MB
        $file = UploadedFile::fake()->create('large-image.jpg', 6000, 'image/jpeg');

        $response = $this->actingAs($this->user, 'sanctum')
                        ->postJson('/api/images/products/' . $product->id, [
                            'images' => [$file],
                        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['images.0']);
    }
}