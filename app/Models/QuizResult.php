<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'quiz_id',
        'score',
        'total_points',
        'percentage',
        'answers',
        'recommendations',
        'completed_at',
    ];

    protected $casts = [
        'score' => 'integer',
        'total_points' => 'integer',
        'percentage' => 'decimal:2',
        'answers' => 'array',
        'recommendations' => 'array',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the user that owns the quiz result.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the quiz.
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(BeautyQuiz::class, 'quiz_id');
    }

    /**
     * Scope a query to only include results for a specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include results for a specific quiz.
     */
    public function scopeForQuiz($query, $quizId)
    {
        return $query->where('quiz_id', $quizId);
    }

    /**
     * Scope a query to order results by completion date.
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('completed_at', 'desc');
    }

    /**
     * Scope a query to only include high scores.
     */
    public function scopeHighScores($query, $threshold = 80)
    {
        return $query->where('percentage', '>=', $threshold);
    }

    /**
     * Get the grade based on percentage.
     */
    public function getGradeAttribute()
    {
        $percentage = $this->percentage;

        if ($percentage >= 90) {
            return 'A';
        } elseif ($percentage >= 80) {
            return 'B';
        } elseif ($percentage >= 70) {
            return 'C';
        } elseif ($percentage >= 60) {
            return 'D';
        } else {
            return 'F';
        }
    }

    /**
     * Check if the result is passing.
     */
    public function isPassing()
    {
        return $this->percentage >= 60;
    }
}