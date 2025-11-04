<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'sku' => $this->sku,
            'price' => $this->price,
            'original_price' => $this->original_price,
            'discount_percentage' => $this->discount_percentage,
            'brand' => $this->brand,
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                    'slug' => $this->category->slug,
                ];
            }),
            'skin_type' => $this->skin_type,
            'tags' => $this->tags,
            'images' => $this->images,
            'dimensions' => $this->dimensions,
            'weight' => $this->weight,
            'stock_quantity' => $this->stock_quantity,
            'min_stock_level' => $this->min_stock_level,
            'is_active' => $this->is_active,
            'is_featured' => $this->is_featured,
            'is_virtual' => $this->is_virtual,
            'average_rating' => $this->average_rating,
            'review_count' => $this->review_count,
            'view_count' => $this->view_count,
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),
            'ingredients' => ProductIngredientResource::collection($this->whenLoaded('ingredients')),
            'reviews' => ProductReviewResource::collection($this->whenLoaded('reviews')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}