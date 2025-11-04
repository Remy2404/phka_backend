<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FAQResource extends JsonResource
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
            'question' => $this->question,
            'answer' => $this->answer,
            'category' => $this->category,
            'tags' => $this->tags,
            'is_featured' => $this->is_featured,
            'is_active' => $this->is_active,
            'view_count' => $this->view_count,
            'helpful_count' => $this->helpful_count,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}