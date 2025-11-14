<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BeautyTip;
use App\Models\Brand;
use App\Models\Category;
use App\Models\ProductImage;
use App\Models\ReviewImage;
use App\Models\Store;
use App\Models\TutorialVideo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Images",
 *     description="Image upload and management endpoints"
 * )
 */
class ImageController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/images/products/{productId}",
     *     tags={"Images"},
     *     summary="Upload product images",
     *     description="Upload multiple images for a product (max 10, 5MB each)",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="productId",
     *         in="path",
     *         required=true,
     *         description="Product ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"images"},
     *                 @OA\Property(
     *                     property="images[]",
     *                     type="array",
     *                     @OA\Items(type="string", format="binary"),
     *                     description="Product images (JPEG, PNG, JPG, WEBP)"
     *                 ),
     *                 @OA\Property(property="variant_id", type="integer", nullable=true, description="Product variant ID"),
     *                 @OA\Property(
     *                     property="alt_texts[]",
     *                     type="array",
     *                     @OA\Items(type="string"),
     *                     description="Alt texts for images"
     *                 ),
     *                 @OA\Property(
     *                     property="is_primary[]",
     *                     type="array",
     *                     @OA\Items(type="boolean"),
     *                     description="Mark image as primary"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Images uploaded successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product images uploaded successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(response=404, description="Product not found"),
     *     @OA\Response(response=422, description="Validation failed")
     * )
     *
     * Upload product images.
     */
    public function uploadProductImages(Request $request, $productId)
    {
        $validator = Validator::make($request->all(), [
            'images' => 'required|array|min:1|max:10',
            'images.*' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120', // 5MB max
            'variant_id' => 'nullable|exists:product_variants,id',
            'alt_texts' => 'nullable|array',
            'alt_texts.*' => 'nullable|string|max:255',
            'is_primary' => 'nullable|array',
            'is_primary.*' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $product = \App\Models\Product::findOrFail($productId);
        $uploadedImages = [];
        $variantId = $request->variant_id;

        foreach ($request->file('images') as $index => $image) {
            $imageName = time() . '_' . $productId . '_' . ($variantId ? $variantId . '_' : '') . $index . '.' . $image->extension();
            $path = $image->storeAs('public/products', $imageName);

            $productImage = ProductImage::create([
                'product_id' => $productId,
                'variant_id' => $variantId,
                'image_url' => $imageName,
                'alt_text' => $request->alt_texts[$index] ?? null,
                'is_primary' => $request->is_primary[$index] ?? false,
                'sort_order' => ProductImage::where('product_id', $productId)->max('sort_order') + 1,
            ]);

            $uploadedImages[] = [
                'id' => $productImage->id,
                'image_url' => asset('storage/products/' . $imageName),
                'alt_text' => $productImage->alt_text,
                'is_primary' => $productImage->is_primary,
                'sort_order' => $productImage->sort_order,
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Product images uploaded successfully',
            'data' => $uploadedImages
        ]);
    }

    /**
     * Upload review images.
     */
    public function uploadReviewImages(Request $request, $reviewId)
    {
        $validator = Validator::make($request->all(), [
            'images' => 'required|array|min:1|max:5',
            'images.*' => 'required|image|mimes:jpeg,png,jpg,webp|max:3072', // 3MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $review = \App\Models\ProductReview::findOrFail($reviewId);
        $uploadedImages = [];

        foreach ($request->file('images') as $index => $image) {
            $imageName = time() . '_' . $reviewId . '_' . $index . '.' . $image->extension();
            $path = $image->storeAs('public/reviews', $imageName);

            $reviewImage = ReviewImage::create([
                'review_id' => $reviewId,
                'image_url' => $imageName,
            ]);

            $uploadedImages[] = [
                'id' => $reviewImage->id,
                'image_url' => asset('storage/reviews/' . $imageName),
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Review images uploaded successfully',
            'data' => $uploadedImages
        ]);
    }

    /**
     * Upload category image.
     */
    public function uploadCategoryImage(Request $request, $categoryId)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048', // 2MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $category = Category::findOrFail($categoryId);

        // Delete old image if exists
        if ($category->image && Storage::exists('public/categories/' . $category->image)) {
            Storage::delete('public/categories/' . $category->image);
        }

        $imageName = time() . '_' . $categoryId . '.' . $request->image->extension();
        $request->image->storeAs('public/categories', $imageName);

        $category->update(['image' => $imageName]);

        return response()->json([
            'success' => true,
            'message' => 'Category image uploaded successfully',
            'data' => [
                'image_url' => asset('storage/categories/' . $imageName)
            ]
        ]);
    }

    /**
     * Upload brand logo.
     */
    public function uploadBrandLogo(Request $request, $brandId)
    {
        $validator = Validator::make($request->all(), [
            'logo' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048', // 2MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $brand = Brand::findOrFail($brandId);

        // Delete old logo if exists
        if ($brand->logo && Storage::exists('public/brands/' . $brand->logo)) {
            Storage::delete('public/brands/' . $brand->logo);
        }

        $logoName = time() . '_' . $brandId . '.' . $request->logo->extension();
        $request->logo->storeAs('public/brands', $logoName);

        $brand->update(['logo' => $logoName]);

        return response()->json([
            'success' => true,
            'message' => 'Brand logo uploaded successfully',
            'data' => [
                'logo_url' => asset('storage/brands/' . $logoName)
            ]
        ]);
    }

    /**
     * Upload store image.
     */
    public function uploadStoreImage(Request $request, $storeId)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:3072', // 3MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $store = Store::findOrFail($storeId);

        // Delete old image if exists
        if ($store->image_url && Storage::exists('public/stores/' . basename($store->image_url))) {
            Storage::delete('public/stores/' . basename($store->image_url));
        }

        $imageName = time() . '_' . $storeId . '.' . $request->image->extension();
        $request->image->storeAs('public/stores', $imageName);

        $store->update(['image_url' => asset('storage/stores/' . $imageName)]);

        return response()->json([
            'success' => true,
            'message' => 'Store image uploaded successfully',
            'data' => [
                'image_url' => asset('storage/stores/' . $imageName)
            ]
        ]);
    }

    /**
     * Upload beauty tip image.
     */
    public function uploadBeautyTipImage(Request $request, $tipId)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:3072', // 3MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $tip = BeautyTip::findOrFail($tipId);

        // Delete old image if exists
        if ($tip->image_url && Storage::exists('public/beauty-tips/' . basename($tip->image_url))) {
            Storage::delete('public/beauty-tips/' . basename($tip->image_url));
        }

        $imageName = time() . '_' . $tipId . '.' . $request->image->extension();
        $request->image->storeAs('public/beauty-tips', $imageName);

        $tip->update(['image_url' => asset('storage/beauty-tips/' . $imageName)]);

        return response()->json([
            'success' => true,
            'message' => 'Beauty tip image uploaded successfully',
            'data' => [
                'image_url' => asset('storage/beauty-tips/' . $imageName)
            ]
        ]);
    }

    /**
     * Upload tutorial video thumbnail.
     */
    public function uploadTutorialThumbnail(Request $request, $videoId)
    {
        $validator = Validator::make($request->all(), [
            'thumbnail' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048', // 2MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $video = TutorialVideo::findOrFail($videoId);

        // Delete old thumbnail if exists
        if ($video->thumbnail_url && Storage::exists('public/tutorials/' . basename($video->thumbnail_url))) {
            Storage::delete('public/tutorials/' . basename($video->thumbnail_url));
        }

        $thumbnailName = time() . '_' . $videoId . '.' . $request->thumbnail->extension();
        $request->thumbnail->storeAs('public/tutorials', $thumbnailName);

        $video->update(['thumbnail_url' => asset('storage/tutorials/' . $thumbnailName)]);

        return response()->json([
            'success' => true,
            'message' => 'Tutorial thumbnail uploaded successfully',
            'data' => [
                'thumbnail_url' => asset('storage/tutorials/' . $thumbnailName)
            ]
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/images/products/{imageId}",
     *     tags={"Images"},
     *     summary="Delete product image",
     *     description="Delete a specific product image",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="imageId",
     *         in="path",
     *         required=true,
     *         description="Product image ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Image deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product image deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Image not found")
     * )
     *
     * Delete product image.
     */
    public function deleteProductImage($imageId)
    {
        $image = ProductImage::findOrFail($imageId);

        if (Storage::exists('public/products/' . $image->image_url)) {
            Storage::delete('public/products/' . $image->image_url);
        }

        $image->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product image deleted successfully'
        ]);
    }

    /**
     * Delete review image.
     */
    public function deleteReviewImage($imageId)
    {
        $image = ReviewImage::findOrFail($imageId);

        if (Storage::exists('public/reviews/' . $image->image_url)) {
            Storage::delete('public/reviews/' . $image->image_url);
        }

        $image->delete();

        return response()->json([
            'success' => true,
            'message' => 'Review image deleted successfully'
        ]);
    }
}