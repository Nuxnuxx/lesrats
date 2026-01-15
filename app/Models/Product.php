<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    protected $fillable = [
        'shop_id',
        'title',
        'description',
        'price',
        'quantity',
        'sku',
        'etsy_listing_id',
        'etsy_state',
        'etsy_synced_at',
        'aliexpress_product_id',
        'aliexpress_url',
        'images',
        'is_active',
        'auto_sync',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'quantity' => 'integer',
        'images' => 'array',
        'is_active' => 'boolean',
        'auto_sync' => 'boolean',
        'etsy_synced_at' => 'datetime',
    ];

    /**
     * Get the shop that owns the product.
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * Check if product is synced with Etsy.
     */
    public function isSyncedWithEtsy(): bool
    {
        return !is_null($this->etsy_listing_id);
    }

    /**
     * Check if product needs sync (modified after last sync).
     */
    public function needsSync(): bool
    {
        if (!$this->isSyncedWithEtsy()) {
            return false;
        }

        return $this->etsy_synced_at === null || $this->updated_at > $this->etsy_synced_at;
    }

    /**
     * Get the first image URL.
     */
    public function getFirstImageAttribute(): ?string
    {
        $images = $this->images ?? [];
        return $images[0] ?? null;
    }
}
