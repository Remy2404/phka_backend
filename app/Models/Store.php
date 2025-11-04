<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'address',
        'phone',
        'email',
        'website',
        'latitude',
        'longitude',
        'opening_hours',
        'is_active',
        'manager_name',
        'manager_phone',
        'image_url',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'opening_hours' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the products available at this store.
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'store_products')
                    ->withPivot('quantity', 'price_override')
                    ->withTimestamps();
    }

    /**
     * Scope a query to only include active stores.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to search stores by name or address.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where('name', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
    }

    /**
     * Scope a query to find stores within a radius.
     */
    public function scopeWithinRadius($query, $latitude, $longitude, $radiusKm = 10)
    {
        // Using Haversine formula for distance calculation
        $haversine = "(6371 * acos(cos(radians({$latitude})) * cos(radians(latitude)) * cos(radians(longitude) - radians({$longitude})) + sin(radians({$latitude})) * sin(radians(latitude))))";

        return $query->selectRaw("*, {$haversine} as distance")
                    ->having('distance', '<=', $radiusKm)
                    ->orderBy('distance');
    }

    /**
     * Check if the store is currently open.
     */
    public function isOpen()
    {
        if (!$this->opening_hours) {
            return true; // Assume open if no hours specified
        }

        $now = now();
        $dayOfWeek = strtolower($now->format('l'));
        $currentTime = $now->format('H:i');

        if (!isset($this->opening_hours[$dayOfWeek])) {
            return false;
        }

        $hours = $this->opening_hours[$dayOfWeek];

        if (!isset($hours['open']) || !isset($hours['close'])) {
            return false;
        }

        return $currentTime >= $hours['open'] && $currentTime <= $hours['close'];
    }

    /**
     * Get the formatted opening hours for today.
     */
    public function getTodaysHoursAttribute()
    {
        if (!$this->opening_hours) {
            return 'Hours not specified';
        }

        $dayOfWeek = strtolower(now()->format('l'));

        if (!isset($this->opening_hours[$dayOfWeek])) {
            return 'Closed today';
        }

        $hours = $this->opening_hours[$dayOfWeek];

        if (!isset($hours['open']) || !isset($hours['close'])) {
            return 'Closed today';
        }

        return $hours['open'] . ' - ' . $hours['close'];
    }

    /**
     * Get the full address with coordinates.
     */
    public function getCoordinatesAttribute()
    {
        return [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }
}