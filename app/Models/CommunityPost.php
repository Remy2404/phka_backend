<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommunityPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'content',
        'category',
        'tags',
        'image_url',
        'video_url',
        'is_featured',
        'is_active',
        'view_count',
        'like_count',
        'comment_count',
        'published_at',
    ];

    protected $casts = [
        'tags' => 'array',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'view_count' => 'integer',
        'like_count' => 'integer',
        'comment_count' => 'integer',
        'published_at' => 'datetime',
    ];

    /**
     * Get the user that owns the post.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the comments for the post.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(PostComment::class);
    }

    /**
     * Scope a query to only include active posts.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include featured posts.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope a query to only include published posts.
     */
    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at');
    }

    /**
     * Scope a query to only include posts by category.
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope a query to order posts by popularity.
     */
    public function scopePopular($query)
    {
        return $query->orderBy('view_count', 'desc');
    }

    /**
     * Scope a query to order posts by most liked.
     */
    public function scopeMostLiked($query)
    {
        return $query->orderBy('like_count', 'desc');
    }

    /**
     * Scope a query to order posts by most commented.
     */
    public function scopeMostCommented($query)
    {
        return $query->orderBy('comment_count', 'desc');
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
     * Increment the comment count.
     */
    public function incrementComments()
    {
        $this->increment('comment_count');
    }

    /**
     * Get the excerpt of the content.
     */
    public function getExcerptAttribute()
    {
        return substr(strip_tags($this->content), 0, 150) . '...';
    }
}