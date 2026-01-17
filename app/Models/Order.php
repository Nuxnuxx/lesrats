<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    // Status constants
    public const STATUS_NEW = 'new';
    public const STATUS_ORDERED = 'ordered';
    public const STATUS_SHIPPED = 'shipped';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'shop_id',
        'etsy_receipt_id',
        'customer_name',
        'customer_email',
        'total_price',
        'total_cost',
        'total_profit',
        'currency',
        'status',
        'ordered_at',
        'shipped_at',
        'delivered_at',
        'completed_at',
        'etsy_data',
        'shipping_address',
        'notes',
    ];

    protected $casts = [
        'total_price' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'total_profit' => 'decimal:2',
        'ordered_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'completed_at' => 'datetime',
        'etsy_data' => 'array',
        'shipping_address' => 'array',
    ];

    /**
     * Get all available statuses
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_NEW,
            self::STATUS_ORDERED,
            self::STATUS_SHIPPED,
            self::STATUS_DELIVERED,
            self::STATUS_COMPLETED,
        ];
    }

    // Relationships

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // Accessors

    /**
     * Get profit margin as percentage
     */
    public function getProfitMarginAttribute(): float
    {
        if ($this->total_price <= 0) {
            return 0;
        }

        return round(($this->total_profit / $this->total_price) * 100, 1);
    }

    /**
     * Get formatted total price with currency
     */
    public function getFormattedTotalAttribute(): string
    {
        return number_format($this->total_price, 2) . ' ' . $this->currency;
    }

    /**
     * Get formatted profit with currency
     */
    public function getFormattedProfitAttribute(): string
    {
        $prefix = $this->total_profit >= 0 ? '+' : '';
        return $prefix . number_format($this->total_profit, 2) . ' ' . $this->currency;
    }

    /**
     * Get status color for UI badges
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_NEW => 'yellow',
            self::STATUS_ORDERED => 'blue',
            self::STATUS_SHIPPED => 'indigo',
            self::STATUS_DELIVERED => 'green',
            self::STATUS_COMPLETED => 'gray',
            default => 'gray',
        };
    }

    /**
     * Get human-readable status label
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_NEW => 'New Order',
            self::STATUS_ORDERED => 'Ordered from Supplier',
            self::STATUS_SHIPPED => 'Shipped',
            self::STATUS_DELIVERED => 'Delivered',
            self::STATUS_COMPLETED => 'Completed',
            default => ucfirst($this->status),
        };
    }

    /**
     * Check if order has any dropship items that need ordering
     */
    public function getNeedsSupplierOrderAttribute(): bool
    {
        if ($this->status !== self::STATUS_NEW) {
            return false;
        }

        return $this->items()
            ->where('source_type', 'aliexpress')
            ->exists();
    }

    /**
     * Check if order contains only digital items
     */
    public function getIsDigitalOnlyAttribute(): bool
    {
        return $this->items()->where('is_digital', false)->doesntExist();
    }

    /**
     * Get the shipping address formatted as a string
     */
    public function getFormattedAddressAttribute(): string
    {
        if (!$this->shipping_address) {
            return '';
        }

        $addr = $this->shipping_address;
        $parts = array_filter([
            $addr['name'] ?? null,
            $addr['first_line'] ?? $addr['street'] ?? null,
            $addr['second_line'] ?? null,
            implode(' ', array_filter([
                $addr['zip'] ?? $addr['postal_code'] ?? null,
                $addr['city'] ?? null,
            ])),
            $addr['country_name'] ?? $addr['country'] ?? null,
        ]);

        return implode("\n", $parts);
    }

    // Query Scopes

    public function scopeNew($query)
    {
        return $query->where('status', self::STATUS_NEW);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', [
            self::STATUS_NEW,
            self::STATUS_ORDERED,
            self::STATUS_SHIPPED,
        ]);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);
    }

    // Methods

    /**
     * Recalculate totals from order items
     */
    public function recalculateTotals(): void
    {
        $this->total_cost = $this->items->sum(fn ($item) => $item->cost * $item->quantity);
        $this->total_profit = $this->total_price - $this->total_cost;
        $this->save();
    }

    /**
     * Mark order as ordered from supplier
     */
    public function markAsOrdered(): void
    {
        $this->update([
            'status' => self::STATUS_ORDERED,
            'ordered_at' => now(),
        ]);
    }

    /**
     * Mark order as shipped
     */
    public function markAsShipped(): void
    {
        $this->update([
            'status' => self::STATUS_SHIPPED,
            'shipped_at' => now(),
        ]);
    }

    /**
     * Mark order as delivered
     */
    public function markAsDelivered(): void
    {
        $this->update([
            'status' => self::STATUS_DELIVERED,
            'delivered_at' => now(),
        ]);
    }

    /**
     * Mark order as completed
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }
}
