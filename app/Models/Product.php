<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

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
        'shipping_fee',
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
        'deleted_images',
        'deleted_real_images',
        'apply_logo',
        'main_color',
        'secondary_color',
        'materials',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'country_prices' => 'array',
        'price_us' => 'decimal:2',
        'price_other' => 'decimal:2',

        'quantity' => 'integer',
        'images' => 'array',
        'real_images' => 'array',
        'deleted_images' => 'array',
        'deleted_real_images' => 'array',
        'tags' => 'array',
        'sizes' => 'array',
        'is_digital' => 'boolean',
        'apply_logo' => 'boolean',
        'materials' => 'array',
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
    // CONSTANTS
    // ============================================

    public const ETSY_COLORS = [
        'Beige', 'Noir', 'Bleu', 'Bronze', 'Marron', 'Transparent', 'Cuivre', 'Or',
        'Gris', 'Vert', 'Orange', 'Rose', 'Violet', 'Arc-en-ciel', 'Rouge', 'Or rose',
        'Argent', 'Blanc', 'Jaune',
    ];

    public const ETSY_MATERIALS = [
        'Acrylique', 'Bambou', 'Toile', 'Cachemire', 'Céramique', 'Mousseline de soie', 'Coton',
        'Jean', 'Simili-cuir', 'Feutre', 'Polaire', 'Fourrure', 'Verre', 'Or', 'Jute',
        'Dentelle', 'Cuir', 'Lin', 'Métal', 'Modal', 'Nylon', 'Polyester', 'Rayonne',
        'Satin', 'Soie', 'Argent', 'Spandex', 'Daim', 'Velours', 'Viscose',
        'Osier', 'Bois', 'Laine',
    ];

    public const ETSY_FEE_RATE = 0.105;    // 10.5% Etsy commission

    public const ETSY_FIXED_FEE = 0.47;     // Fixed fee per transaction

    public const URSSAF_RATE = 0.123;        // 12.3% URSSAF micro-entrepreneur

    // ============================================
    // ACCESSORS
    // ============================================

    /**
     * Resolve a stored image path to a full URL.
     * Handles: storage-relative paths (products/...), /storage/ paths, full URLs.
     */
    public static function resolveImageUrl(string $path): string
    {
        // Already a full URL (external or cloud CDN)
        if (str_starts_with($path, 'http')) {
            return $path;
        }

        // Legacy /storage/ prefix — strip it to get the relative path
        $relativePath = preg_replace('#^/storage/#', '', $path);

        return Storage::disk('public')->url($relativePath);
    }

    /**
     * Get real_images as resolved URLs.
     */
    public function getRealImageUrlsAttribute(): array
    {
        return array_map([self::class, 'resolveImageUrl'], $this->real_images ?? []);
    }

    /**
     * Get deleted_real_images as resolved URLs.
     * Handles both legacy string format and new {path, deleted_at} object format.
     */
    public function getDeletedRealImageUrlsAttribute(): array
    {
        return array_map(function ($item) {
            $path = is_array($item) ? $item['path'] : $item;

            return self::resolveImageUrl($path);
        }, $this->deleted_real_images ?? []);
    }

    /**
     * Get the effective shipping fee: product-level override or shop default.
     */
    public function getEffectiveShippingFeeAttribute(): float
    {
        if ($this->shipping_fee !== null) {
            return (float) $this->shipping_fee;
        }

        return $this->shop->default_shipping_fee;
    }

    /**
     * Calculate estimated net profit per sale (after Etsy fees, cost, and URSSAF).
     */
    public function getEtsyProfitAttribute(): float
    {
        $price = (float) ($this->price ?? 0);
        $shipping = $this->effectiveShippingFee;
        $cost = (float) ($this->cost_price ?? 0);

        $etsyRevenue = ($price + $shipping) * (1 - self::ETSY_FEE_RATE) - self::ETSY_FIXED_FEE;
        $urssaf = $etsyRevenue * self::URSSAF_RATE;

        return round($etsyRevenue - $cost - $urssaf, 2);
    }

    /**
     * Get Etsy fees amount for this product.
     */
    public function getEtsyFeesAttribute(): float
    {
        $price = (float) ($this->price ?? 0);
        $shipping = $this->effectiveShippingFee;

        return round(($price + $shipping) * self::ETSY_FEE_RATE + self::ETSY_FIXED_FEE, 2);
    }

    /**
     * Get the first image URL.
     */
    public function getFirstImageAttribute(): ?string
    {
        $images = $this->images ?? [];

        return $images[0] ?? null;
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
