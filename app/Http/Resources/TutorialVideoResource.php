<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TutorialVideoResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'video_url' => $this->video_url,
            'thumbnail' => $this->thumbnail,
            'duration' => $this->duration,
            'category' => $this->category,
            'skill_level' => $this->skill_level,
            'tags' => $this->tags,
            'products_used' => $this->products_used,
            'steps' => $this->steps,
            'is_featured' => $this->is_featured,
            'is_active' => $this->is_active,
            'view_count' => $this->view_count,
            'like_count' => $this->like_count,
            'author' => $this->whenLoaded('author', function () {
                return [
                    'id' => $this->author->id,
                    'name' => $this->author->name,
                    'avatar' => $this->author->avatar,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}