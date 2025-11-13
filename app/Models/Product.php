<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'brand_id',
        'name',
        'slug',
        'description',
        'short_description',
        'base_price',
        'original_price',
        'is_on_sale',
        'discount_percentage',
        'sku',
        'barcode',
        'stock_quantity',
        'weight',
        'dimensions',
        'ingredients',
        'how_to_use',
        'benefits',
        'warnings',
        'skin_types',
        'skin_concerns',
        'is_vegan',
        'is_cruelty_free',
        'is_organic',
        'is_paraben_free',
        'is_sulfate_free',
        'is_featured',
        'is_new_arrival',
        'is_best_seller',
        'is_limited_edition',
        'is_active',
        'rating',
        'review_count',
        'view_count',
        'purchase_count',
        'published_at',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'original_price' => 'decimal:2',
        'is_on_sale' => 'boolean',
        'discount_percentage' => 'integer',
        'stock_quantity' => 'integer',
        'weight' => 'decimal:3',
        'dimensions' => 'array',
        'skin_types' => 'array',
        'skin_concerns' => 'array',
        'is_vegan' => 'boolean',
        'is_cruelty_free' => 'boolean',
        'is_organic' => 'boolean',
        'is_paraben_free' => 'boolean',
        'is_sulfate_free' => 'boolean',
        'is_featured' => 'boolean',
        'is_new_arrival' => 'boolean',
        'is_best_seller' => 'boolean',
        'is_limited_edition' => 'boolean',
        'is_active' => 'boolean',
        'rating' => 'decimal:2',
        'review_count' => 'integer',
        'view_count' => 'integer',
        'purchase_count' => 'integer',
        'published_at' => 'datetime',
    ];

    /**
     * Get the category that owns the product.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the brand that owns the product.
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Get the variants for the product.
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * Get the images for the product.
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    /**
     * Get the ingredients for the product.
     */
    public function ingredients(): HasMany
    {
        return $this->hasMany(ProductIngredient::class);
    }

    /**
     * Get the reviews for the product.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    /**
     * Get the cart items for the product.
     */
    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Get the order items for the product.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the wishlist items for the product.
     */
    public function wishlistItems(): HasMany
    {
        return $this->hasMany(WishlistItem::class);
    }

    /**
     * Get the price alerts for the product.
     */
    public function priceAlerts(): HasMany
    {
        return $this->hasMany(PriceAlert::class);
    }

    /**
     * Scope a query to only include active products.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include featured products.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope a query to only include in-stock products.
     */
    public function scopeInStock($query)
    {
        return $query->where('stock_status', 'in_stock');
    }

    /**
     * Get the current price (sale price if available, otherwise regular price).
     */
    public function getCurrentPriceAttribute()
    {
        return $this->sale_price ?? $this->price;
    }

    /**
     * Check if the product is on sale.
     */
    public function getIsOnSaleAttribute()
    {
        return $this->sale_price !== null && $this->sale_price < $this->price;
    }
}