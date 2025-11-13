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

class ImageUploadTest extends TestCase
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
        $product = Product::factory()->create();
        $file = UploadedFile::fake()->image('product.jpg');

        $response = $this->actingAs($this->user, 'sanctum')
                        ->postJson('/api/images/products/' . $product->id, [
                            'images' => [$file],
                        ]);

        // Debug the response
        if ($response->status() !== 200) {
            dump($response->json());
        }

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                ]);
    }

    public function test_can_upload_avatar()
    {
        $file = UploadedFile::fake()->image('avatar.jpg');

        $response = $this->actingAs($this->user, 'sanctum')
                        ->postJson('/api/user/avatar', [
                            'avatar' => $file,
                        ]);

        // Debug the response
        if ($response->status() !== 200) {
            dump($response->json());
        }

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                ]);

        // Assert the file was stored
        Storage::disk('public')->assertExists('avatars/' . $this->user->fresh()->avatar);
    }
}