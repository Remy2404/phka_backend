<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FAQ extends Model
{
    use HasFactory;

    protected $fillable = [
        'question',
        'answer',
        'category',
        'is_featured',
        'is_active',
        'view_count',
        'order',
        'created_by',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'view_count' => 'integer',
        'order' => 'integer',
    ];

    /**
     * Get the user that created the FAQ.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope a query to only include active FAQs.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include featured FAQs.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope a query to only include FAQs by category.
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope a query to order FAQs by their order field.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('created_at', 'desc');
    }

    /**
     * Scope a query to search FAQs by question or answer.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where('question', 'like', "%{$search}%")
                    ->orWhere('answer', 'like', "%{$search}%");
    }

    /**
     * Scope a query to order FAQs by popularity.
     */
    public function scopePopular($query)
    {
        return $query->orderBy('view_count', 'desc');
    }

    /**
     * Increment the view count.
     */
    public function incrementViews()
    {
        $this->increment('view_count');
    }

    /**
     * Get the excerpt of the answer.
     */
    public function getAnswerExcerptAttribute()
    {
        return substr(strip_tags($this->answer), 0, 100) . '...';
    }
}