<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecentlyViewed extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'viewed_at',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
    ];

    /**
     * Get the user that owns the recently viewed item.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope a query to only include items from a specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include items for a specific product.
     */
    public function scopeForProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope a query to order by most recently viewed.
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('viewed_at', 'desc');
    }

    /**
     * Scope a query to limit results.
     */
    public function scopeLimitResults($query, $limit = 50)
    {
        return $query->limit($limit);
    }

    /**
     * Update the viewed timestamp for a user's product view.
     */
    public static function updateViewed($userId, $productId)
    {
        return static::updateOrCreate(
            [
                'user_id' => $userId,
                'product_id' => $productId,
            ],
            [
                'viewed_at' => now(),
            ]
        );
    }

    /**
     * Get recently viewed products for a user.
     */
    public static function getRecentForUser($userId, $limit = 20)
    {
        return static::with('product')
                    ->forUser($userId)
                    ->recent()
                    ->limitResults($limit)
                    ->get();
    }
}