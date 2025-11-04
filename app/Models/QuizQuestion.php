<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'quiz_id',
        'question_text',
        'question_type',
        'options',
        'correct_answer',
        'points',
        'explanation',
        'order',
    ];

    protected $casts = [
        'options' => 'array',
        'points' => 'integer',
        'order' => 'integer',
    ];

    /**
     * Get the quiz that owns the question.
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(BeautyQuiz::class, 'quiz_id');
    }

    /**
     * Scope a query to order questions by their order field.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    /**
     * Check if the question is multiple choice.
     */
    public function isMultipleChoice()
    {
        return $this->question_type === 'multiple_choice';
    }

    /**
     * Check if the question is true/false.
     */
    public function isTrueFalse()
    {
        return $this->question_type === 'true_false';
    }

    /**
     * Check if the question is open ended.
     */
    public function isOpenEnded()
    {
        return $this->question_type === 'open_ended';
    }

    /**
     * Get the options as a formatted array.
     */
    public function getFormattedOptionsAttribute()
    {
        if (!$this->options) {
            return [];
        }

        return collect($this->options)->map(function ($option, $index) {
            return [
                'id' => $index + 1,
                'text' => $option,
            ];
        })->toArray();
    }
}