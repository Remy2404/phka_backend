<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductReviewResource extends JsonResource
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
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'avatar' => $this->user->avatar,
                ];
            }),
            'rating' => $this->rating,
            'title' => $this->title,
            'comment' => $this->comment,
            'images' => $this->images,
            'is_verified_purchase' => $this->is_verified_purchase,
            'is_helpful' => $this->is_helpful,
            'helpful_count' => $this->helpful_count,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}