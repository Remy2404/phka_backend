<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
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
            'cart_id' => $this->cart_id,
            'product_id' => $this->product_id,
            'product' => $this->whenLoaded('product', function () {
                return [
                    'id' => $this->product->id,
                    'name' => $this->product->name,
                    'sku' => $this->product->sku,
                    'price' => $this->product->price,
                    'images' => $this->product->images,
                ];
            }),
            'variant_id' => $this->variant_id,
            'variant' => $this->whenLoaded('variant', function () {
                return [
                    'id' => $this->variant->id,
                    'variant_type' => $this->variant->variant_type,
                    'variant_value' => $this->variant->variant_value,
                    'price' => $this->variant->price,
                ];
            }),
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'total_price' => $this->total_price,
            'added_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}