<?php

// Quick test script to debug the ImageController response
require_once 'bootstrap/app.php';

use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\ImageController;
use App\Models\Product;
use App\Models\Category;
use App\Models\User;

// Create app instance
$app = \Illuminate\Foundation\Application::configure(basePath: __DIR__)
    ->withRouting(
        web: __DIR__.'/routes/web.php',
        api: __DIR__.'/routes/api.php',
        commands: __DIR__.'/routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (\Illuminate\Foundation\Configuration\Middleware $middleware) {
        //
    })
    ->withExceptions(function (\Illuminate\Foundation\Configuration\Exceptions $exceptions) {
        //
    })
    ->create();

// Bootstrap the application
$app->boot();

// Create test data
$category = Category::factory()->create();
$product = Product::factory()->create(['category_id' => $category->id]);

// Create fake uploaded file
$file = UploadedFile::fake()->image('test.jpg');

// Create request
$request = new Request();
$request->files->set('images', [$file]);

// Create controller and test
$controller = new ImageController();
try {
    $response = $controller->uploadProductImages($request, $product->id);
    echo "Response: " . $response->getContent() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}