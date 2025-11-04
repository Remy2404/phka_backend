<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'target_price',
        'is_active',
        'alert_sent',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'target_price' => 'decimal:2',
        'is_active' => 'boolean',
        'alert_sent' => 'boolean',
    ];

    /**
     * Get the user that owns the price alert.
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
     * Scope a query to only include active alerts.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include alerts that haven't been sent.
     */
    public function scopePending($query)
    {
        return $query->where('alert_sent', false);
    }

    /**
     * Scope a query to only include alerts for a specific product.
     */
    public function scopeForProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope a query to only include alerts for a specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Check if the alert should be triggered.
     */
    public function shouldTrigger($currentPrice)
    {
        return $this->is_active &&
               !$this->alert_sent &&
               $currentPrice <= $this->target_price;
    }

    /**
     * Mark the alert as sent.
     */
    public function markAsSent()
    {
        $this->update(['alert_sent' => true]);
    }

    /**
     * Deactivate the alert.
     */
    public function deactivate()
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Get the price difference.
     */
    public function getPriceDifferenceAttribute()
    {
        return $this->product->price - $this->target_price;
    }

    /**
     * Get the percentage savings.
     */
    public function getPercentageSavingsAttribute()
    {
        if ($this->product->original_price > 0) {
            return (($this->product->original_price - $this->target_price) / $this->product->original_price) * 100;
        }

        return 0;
    }
}