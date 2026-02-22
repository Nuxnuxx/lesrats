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
        'tags',
        'sizes',
        'etsy_category',
        'price',
        'cost_price',
        'country_prices',
        'price_us',
        'price_other',

        'is_digital',
        'quantity',
        'low_stock_threshold',
        'aliexpress_product_id',
        'aliexpress_url',
        'source_type',
        'source_url',
        'images',
        'real_images',
        'apply_logo',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'country_prices' => 'array',
        'price_us' => 'decimal:2',
        'price_other' => 'decimal:2',

        'quantity' => 'integer',
        'images' => 'array',
        'real_images' => 'array',
        'tags' => 'array',
        'sizes' => 'array',
        'is_digital' => 'boolean',
        'apply_logo' => 'boolean',
    ];

    // ============================================
    // CONSTANTS
    // ============================================

    public const SOURCE_ALIEXPRESS = 'aliexpress';

    public const SOURCE_PRINTABLES = 'printables';

    public const SOURCE_MANUAL = 'manual';

    // ============================================
    // RELATIONSHIPS
    // ============================================

    /**
     * Get the shop that owns the product.
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    // ============================================
    // ACCESSORS
    // ============================================

    /**
     * Get the first image URL.
     */
    public function getFirstImageAttribute(): ?string
    {
        $images = $this->images ?? [];

        return $images[0] ?? null;
    }

    /**
     * Etsy fee constants.
     */
    public const ETSY_FEE_RATE = 0.105;   // 10.5% commission

    public const ETSY_FIXED_FEE = 0.47;    // Fixed fee per transaction

    /**
     * Calculate estimated Etsy profit per sale.
     *
     * Formula: ((P + shipping) * (1 - k)) - f - cost
     */
    public function getEtsyProfitAttribute(): float
    {
        $price = (float) ($this->price ?? 0);
        $shipping = (float) ($this->shop->shipping_fee ?? 0);
        $cost = (float) ($this->cost_price ?? 0);

        $etsyRevenue = ($price + $shipping) * (1 - self::ETSY_FEE_RATE) - self::ETSY_FIXED_FEE;

        return round($etsyRevenue - $cost, 2);
    }

    /**
     * Get the Etsy revenue (after fees, before cost).
     */
    public function getEtsyRevenueAttribute(): float
    {
        $price = (float) ($this->price ?? 0);
        $shipping = (float) ($this->shop->shipping_fee ?? 0);

        return round(($price + $shipping) * (1 - self::ETSY_FEE_RATE) - self::ETSY_FIXED_FEE, 2);
    }

    /**
     * Get Etsy fees amount for this product.
     */
    public function getEtsyFeesAttribute(): float
    {
        $price = (float) ($this->price ?? 0);
        $shipping = (float) ($this->shop->shipping_fee ?? 0);

        return round(($price + $shipping) * self::ETSY_FEE_RATE + self::ETSY_FIXED_FEE, 2);
    }

    /**
     * Get source icon based on source type.
     */
    public function getSourceIconAttribute(): string
    {
        return match ($this->source_type) {
            self::SOURCE_ALIEXPRESS => '🛒',
            self::SOURCE_PRINTABLES => '🖨️',
            default => '📦',
        };
    }

    /**
     * Get source label in French.
     */
    public function getSourceLabelAttribute(): string
    {
        return match ($this->source_type) {
            self::SOURCE_ALIEXPRESS => 'AliExpress',
            self::SOURCE_PRINTABLES => 'Printables',
            default => 'Manuel',
        };
    }

    /**
     * Get the US total cost from country_prices.
     */
    public function getUsTotalCost(): ?float
    {
        $prices = $this->country_prices ?? [];

        return isset($prices['US']['total']) ? (float) $prices['US']['total'] : null;
    }

    /**
     * Get the highest total cost from "Autres pays" (DE, AT, FR, CA, ES).
     */
    public function getHighestOtherCountryTotal(): ?float
    {
        $prices = $this->country_prices ?? [];
        $otherCountries = ['DE', 'AT', 'FR', 'CA', 'ES'];
        $maxTotal = null;

        foreach ($otherCountries as $country) {
            if (isset($prices[$country]['total'])) {
                $total = (float) $prices[$country]['total'];
                if ($maxTotal === null || $total > $maxTotal) {
                    $maxTotal = $total;
                }
            }
        }

        return $maxTotal;
    }

    /**
     * Get the country with the highest total for "Autres pays".
     */
    public function getHighestOtherCountry(): ?string
    {
        $prices = $this->country_prices ?? [];
        $otherCountries = ['DE', 'AT', 'FR', 'CA', 'ES'];
        $maxTotal = null;
        $maxCountry = null;

        foreach ($otherCountries as $country) {
            if (isset($prices[$country]['total'])) {
                $total = (float) $prices[$country]['total'];
                if ($maxTotal === null || $total > $maxTotal) {
                    $maxTotal = $total;
                    $maxCountry = $country;
                }
            }
        }

        return $maxCountry;
    }

    // ============================================
    // QUERY SCOPES
    // ============================================

    /**
     * Scope by source type.
     */
    public function scopeBySource($query, string $source)
    {
        return $query->where('source_type', $source);
    }

    /**
     * Scope for products with low stock (excluding digital).
     */
    public function scopeLowStock($query)
    {
        return $query->where('is_digital', false)
            ->where('quantity', '<', 999)
            ->whereRaw('quantity <= low_stock_threshold');
    }

    /**
     * Scope for products out of stock.
     */
    public function scopeOutOfStock($query)
    {
        return $query->where('is_digital', false)
            ->where('quantity', '<=', 0);
    }

    // ============================================
    // METHODS
    // ============================================

    /**
     * Check if stock is low.
     */
    public function isLowStock(): bool
    {
        if ($this->is_digital || $this->quantity >= 999) {
            return false;
        }

        return $this->quantity <= ($this->low_stock_threshold ?? 5);
    }

    /**
     * Check if out of stock.
     */
    public function isOutOfStock(): bool
    {
        if ($this->is_digital || $this->quantity >= 999) {
            return false;
        }

        return $this->quantity <= 0;
    }

    /**
     * Check if has unlimited stock (digital or 999+).
     */
    public function hasUnlimitedStock(): bool
    {
        return $this->is_digital || $this->quantity >= 999;
    }
}
