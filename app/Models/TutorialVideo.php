<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TutorialVideo extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'video_url',
        'thumbnail_url',
        'duration',
        'category',
        'tags',
        'difficulty_level',
        'is_featured',
        'is_active',
        'view_count',
        'like_count',
        'created_by',
        'published_at',
    ];

    protected $casts = [
        'tags' => 'array',
        'duration' => 'integer',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'view_count' => 'integer',
        'like_count' => 'integer',
        'published_at' => 'datetime',
    ];

    /**
     * Get the user that created the tutorial video.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope a query to only include active videos.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include featured videos.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope a query to only include published videos.
     */
    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at');
    }

    /**
     * Scope a query to only include videos by category.
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope a query to only include videos by difficulty.
     */
    public function scopeByDifficulty($query, $difficulty)
    {
        return $query->where('difficulty_level', $difficulty);
    }

    /**
     * Scope a query to order videos by popularity.
     */
    public function scopePopular($query)
    {
        return $query->orderBy('view_count', 'desc');
    }

    /**
     * Scope a query to order videos by most liked.
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
     * Get the formatted duration.
     */
    public function getFormattedDurationAttribute()
    {
        $minutes = floor($this->duration / 60);
        $seconds = $this->duration % 60;
        return sprintf('%d:%02d', $minutes, $seconds);
    }

    /**
     * Get the excerpt of the description.
     */
    public function getExcerptAttribute()
    {
        return substr(strip_tags($this->description), 0, 150) . '...';
    }
}