<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductIngredient;
use App\Models\RecentlyViewed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * Get paginated list of products with filters.
     */
    public function index(Request $request)
    {
        $query = Product::with(['category', 'variants', 'reviews', 'images'])
            ->active()
            ->inStock();

        // Apply filters
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%')
                  ->orWhere('tags', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }

        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        if ($request->has('skin_type')) {
            $query->where('skin_type', $request->skin_type);
        }

        if ($request->has('brand')) {
            $query->where('brand', $request->brand);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        switch ($sortBy) {
            case 'price':
                $query->orderBy('price', $sortOrder);
                break;
            case 'rating':
                $query->orderBy('average_rating', $sortOrder);
                break;
            case 'popularity':
                $query->orderBy('view_count', $sortOrder);
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            default:
                $query->orderBy($sortBy, $sortOrder);
        }

        $products = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    /**
     * Get featured products.
     */
    public function featured(Request $request)
    {
        $products = Product::with(['category', 'variants'])
            ->featured()
            ->active()
            ->inStock()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    /**
     * Get product details by ID.
     */
    public function show(Request $request, $id)
    {
        $product = Product::with([
            'category',
            'variants' => function($query) {
                $query->active();
            },
            'reviews' => function($query) {
                $query->with('user:id,name')->latest();
            },
            'ingredients',
            'images'
        ])->findOrFail($id);

        // Track recently viewed for authenticated users
        if ($request->user()) {
            RecentlyViewed::updateViewed($request->user()->id, $product->id);
        }

        // Note: view_count increment removed as column doesn't exist in current schema

        return response()->json([
            'success' => true,
            'data' => $product
        ]);
    }

    /**
     * Get product variants.
     */
    public function variants($productId)
    {
        $product = Product::findOrFail($productId);

        $variants = $product->variants()
            ->active()
            ->orderBy('price')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $variants
        ]);
    }

    /**
     * Get product reviews.
     */
    public function reviews(Request $request, $productId)
    {
        $product = Product::findOrFail($productId);

        $reviews = $product->reviews()
            ->with('user:id,name,avatar')
            ->when($request->has('rating'), function($query) use ($request) {
                return $query->where('rating', $request->rating);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $reviews
        ]);
    }

    /**
     * Get similar products.
     */
    public function similar($productId)
    {
        $product = Product::findOrFail($productId);

        $similarProducts = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $productId)
            ->active()
            ->inStock()
            ->orderBy('average_rating', 'desc')
            ->limit(6)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $similarProducts
        ]);
    }

    /**
     * Get products by category.
     */
    public function byCategory(Request $request, $categoryId)
    {
        $category = Category::findOrFail($categoryId);

        $products = Product::where('category_id', $categoryId)
            ->with(['variants', 'reviews'])
            ->active()
            ->inStock()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => [
                'category' => $category,
                'products' => $products
            ]
        ]);
    }

    /**
     * Search products.
     */
    public function search(Request $request)
    {
        $query = $request->get('q');

        if (!$query) {
            return response()->json([
                'success' => false,
                'message' => 'Search query is required'
            ], 400);
        }

        $products = Product::where(function($q) use ($query) {
            $q->where('name', 'like', '%' . $query . '%')
              ->orWhere('description', 'like', '%' . $query . '%')
              ->orWhere('tags', 'like', '%' . $query . '%')
              ->orWhere('brand', 'like', '%' . $query . '%');
        })
        ->with(['category', 'variants'])
        ->active()
        ->inStock()
        ->orderBy('created_at', 'desc')
        ->limit(50)
        ->get();

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    /**
     * Create a new product (Admin only).
     */
    public function store(StoreProductRequest $request)
    {
        DB::beginTransaction();

        try {
            $product = Product::create($request->validated());

            // Create variants if provided
            if ($request->has('variants')) {
                foreach ($request->variants as $variantData) {
                    ProductVariant::create([
                        'product_id' => $product->id,
                        ...$variantData
                    ]);
                }
            }

            // Create ingredients if provided
            if ($request->has('ingredients')) {
                foreach ($request->ingredients as $ingredientData) {
                    ProductIngredient::create([
                        'product_id' => $product->id,
                        ...$ingredientData
                    ]);
                }
            }

            DB::commit();

            $product->load(['category', 'variants', 'ingredients']);

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully',
                'data' => $product
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update product (Admin only).
     */
    public function update(UpdateProductRequest $request, $id)
    {
        $product = Product::findOrFail($id);

        DB::beginTransaction();

        try {
            $product->update($request->validated());

            // Update variants if provided
            if ($request->has('variants')) {
                // Remove existing variants
                $product->variants()->delete();

                // Create new variants
                foreach ($request->variants as $variantData) {
                    ProductVariant::create([
                        'product_id' => $product->id,
                        ...$variantData
                    ]);
                }
            }

            // Update ingredients if provided
            if ($request->has('ingredients')) {
                // Remove existing ingredients
                $product->ingredients()->delete();

                // Create new ingredients
                foreach ($request->ingredients as $ingredientData) {
                    ProductIngredient::create([
                        'product_id' => $product->id,
                        ...$ingredientData
                    ]);
                }
            }

            DB::commit();

            $product->load(['category', 'variants', 'ingredients']);

            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully',
                'data' => $product
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete product (Admin only).
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully'
        ]);
    }
}