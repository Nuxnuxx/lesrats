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
        'etsy_listing_id',
        'etsy_state',
        'etsy_synced_at',
        'etsy_sync_status',
        'etsy_sync_error',
        'aliexpress_product_id',
        'aliexpress_url',
        'source_type',
        'source_url',
        'images',
        'is_active',
        'auto_sync',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'quantity' => 'integer',
        'images' => 'array',
        'tags' => 'array',
        'is_active' => 'boolean',
        'is_digital' => 'boolean',
        'auto_sync' => 'boolean',
        'etsy_synced_at' => 'datetime',
    ];

    // ============================================
    // CONSTANTS
    // ============================================

    public const SOURCE_ALIEXPRESS = 'aliexpress';
    public const SOURCE_PRINTABLES = 'printables';
    public const SOURCE_MANUAL = 'manual';

    public const SYNC_STATUS_NOT_SYNCED = 'not_synced';
    public const SYNC_STATUS_SYNCED = 'synced';
    public const SYNC_STATUS_PENDING = 'pending';
    public const SYNC_STATUS_ERROR = 'error';

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
        if (!$this->price || $this->price == 0) {
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
     * Get sync status color for UI.
     */
    public function getSyncStatusColorAttribute(): string
    {
        return match ($this->etsy_sync_status) {
            self::SYNC_STATUS_SYNCED => 'green',
            self::SYNC_STATUS_PENDING => 'yellow',
            self::SYNC_STATUS_ERROR => 'red',
            default => 'gray',
        };
    }

    /**
     * Get sync status label in French.
     */
    public function getSyncStatusLabelAttribute(): string
    {
        return match ($this->etsy_sync_status) {
            self::SYNC_STATUS_SYNCED => 'Synchronisé',
            self::SYNC_STATUS_PENDING => 'En attente',
            self::SYNC_STATUS_ERROR => 'Erreur',
            default => 'Non synchronisé',
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
     * Scope for products that need attention (sync errors or not synced).
     */
    public function scopeNeedsAttention($query)
    {
        return $query->where(function ($q) {
            $q->where('etsy_sync_status', self::SYNC_STATUS_ERROR)
              ->orWhere('etsy_sync_status', self::SYNC_STATUS_NOT_SYNCED);
        })->where('is_active', true);
    }

    /**
     * Scope for synced products.
     */
    public function scopeSynced($query)
    {
        return $query->where('etsy_sync_status', self::SYNC_STATUS_SYNCED);
    }

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
     * Check if product is synced with Etsy.
     */
    public function isSyncedWithEtsy(): bool
    {
        return $this->etsy_sync_status === self::SYNC_STATUS_SYNCED;
    }

    /**
     * Check if product needs sync (modified after last sync).
     */
    public function needsSync(): bool
    {
        if (!$this->etsy_listing_id) {
            return true;
        }

        return $this->etsy_synced_at === null || $this->updated_at > $this->etsy_synced_at;
    }

    /**
     * Check if product has sync error.
     */
    public function hasSyncError(): bool
    {
        return $this->etsy_sync_status === self::SYNC_STATUS_ERROR;
    }

    /**
     * Mark product as synced.
     */
    public function markAsSynced(): void
    {
        $this->update([
            'etsy_sync_status' => self::SYNC_STATUS_SYNCED,
            'etsy_sync_error' => null,
            'etsy_synced_at' => now(),
        ]);
    }

    /**
     * Mark product with sync error.
     */
    public function markSyncError(string $error): void
    {
        $this->update([
            'etsy_sync_status' => self::SYNC_STATUS_ERROR,
            'etsy_sync_error' => $error,
        ]);
    }

    /**
     * Mark product as sync pending.
     */
    public function markSyncPending(): void
    {
        $this->update([
            'etsy_sync_status' => self::SYNC_STATUS_PENDING,
        ]);
    }

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
