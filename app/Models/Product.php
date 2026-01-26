<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'shop_id',
        'title',
        'description',
        'tags',
        'price',
        'cost_price',
        'is_digital',
        'quantity',
        'low_stock_threshold',
        'aliexpress_product_id',
        'aliexpress_url',
        'source_type',
        'source_url',
        'images',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'quantity' => 'integer',
        'images' => 'array',
        'tags' => 'array',
        'is_active' => 'boolean',
        'is_digital' => 'boolean',
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

    /**
     * Get order items for this product.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
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
     * Calculate profit margin.
     */
    public function getProfitMarginAttribute(): float
    {
        if (! $this->price || $this->price == 0) {
            return 0;
        }

        $cost = $this->cost_price ?? 0;
        $profit = $this->price - $cost;

        return round(($profit / $this->price) * 100, 1);
    }

    /**
     * Calculate profit amount.
     */
    public function getProfitAmountAttribute(): float
    {
        return $this->price - ($this->cost_price ?? 0);
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
     * Get total sold count from order items.
     */
    public function getTotalSoldAttribute(): int
    {
        return $this->orderItems()->sum('quantity');
    }

    /**
     * Get total revenue from this product.
     */
    public function getTotalRevenueAttribute(): float
    {
        return (float) $this->orderItems()
            ->selectRaw('SUM(price * quantity) as total')
            ->value('total') ?? 0;
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
