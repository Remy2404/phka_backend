<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BeautyTip extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'category',
        'tags',
        'image_url',
        'video_url',
        'difficulty_level',
        'estimated_time',
        'is_featured',
        'is_active',
        'view_count',
        'like_count',
        'created_by',
        'published_at',
    ];

    protected $casts = [
        'tags' => 'array',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'view_count' => 'integer',
        'like_count' => 'integer',
        'estimated_time' => 'integer',
        'published_at' => 'datetime',
    ];

    /**
     * Get the user that created the beauty tip.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope a query to only include active tips.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include featured tips.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope a query to only include published tips.
     */
    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at');
    }

    /**
     * Scope a query to only include tips by category.
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope a query to only include tips by difficulty.
     */
    public function scopeByDifficulty($query, $difficulty)
    {
        return $query->where('difficulty_level', $difficulty);
    }

    /**
     * Scope a query to order tips by popularity.
     */
    public function scopePopular($query)
    {
        return $query->orderBy('view_count', 'desc');
    }

    /**
     * Scope a query to order tips by most liked.
     */
    public function scopeMostLiked($query)
    {
        return $query->orderBy('like_count', 'desc');
    }

    /**
     * Increment the view count.
     */
    public function incrementViews()
    {
        $this->increment('view_count');
    }

    /**
     * Increment the like count.
     */
    public function incrementLikes()
    {
        $this->increment('like_count');
    }

    /**
     * Get the excerpt of the content.
     */
    public function getExcerptAttribute()
    {
        return substr(strip_tags($this->content), 0, 150) . '...';
    }

    /**
     * Get the reading time estimate.
     */
    public function getReadingTimeAttribute()
    {
        $wordCount = str_word_count(strip_tags($this->content));
        $minutes = ceil($wordCount / 200); // Average reading speed
        return $minutes;
    }
}