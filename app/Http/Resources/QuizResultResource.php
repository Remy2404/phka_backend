<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizResultResource extends JsonResource
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
            'user_id' => $this->user_id,
            'quiz_id' => $this->quiz_id,
            'quiz' => $this->whenLoaded('quiz', function () {
                return [
                    'id' => $this->quiz->id,
                    'title' => $this->quiz->title,
                    'category' => $this->quiz->category,
                ];
            }),
            'answers' => $this->answers,
            'score' => $this->score,
            'total_points' => $this->total_points,
            'percentage' => $this->percentage,
            'recommendations' => $this->recommendations,
            'skin_type_result' => $this->skin_type_result,
            'completed_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}