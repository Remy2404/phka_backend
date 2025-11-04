<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
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
            'product_id' => $this->product_id,
            'variant_type' => $this->variant_type,
            'variant_value' => $this->variant_value,
            'sku' => $this->sku,
            'price' => $this->price,
            'original_price' => $this->original_price,
            'stock_quantity' => $this->stock_quantity,
            'is_active' => $this->is_active,
            'image' => $this->image,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}