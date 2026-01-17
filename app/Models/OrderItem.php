<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'etsy_listing_id',
        'etsy_transaction_id',
        'title',
        'quantity',
        'price',
        'cost',
        'profit',
        'sku',
        'source_type',
        'source_url',
        'is_digital',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'cost' => 'decimal:2',
        'profit' => 'decimal:2',
        'is_digital' => 'boolean',
    ];

    // Relationships

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // Accessors

    /**
     * Get total price for this line item (price * quantity)
     */
    public function getTotalPriceAttribute(): float
    {
        return $this->price * $this->quantity;
    }

    /**
     * Get total cost for this line item (cost * quantity)
     */
    public function getTotalCostAttribute(): float
    {
        return $this->cost * $this->quantity;
    }

    /**
     * Get total profit for this line item (profit * quantity)
     */
    public function getTotalProfitAttribute(): float
    {
        return $this->profit * $this->quantity;
    }

    /**
     * Get profit margin as percentage
     */
    public function getProfitMarginAttribute(): float
    {
        if ($this->price <= 0) {
            return 0;
        }

        return round(($this->profit / $this->price) * 100, 1);
    }

    /**
     * Check if this item is from AliExpress (dropship)
     */
    public function getIsDropshipAttribute(): bool
    {
        return $this->source_type === 'aliexpress';
    }

    /**
     * Check if this item is from Printables (digital)
     */
    public function getIsPrintablesAttribute(): bool
    {
        return $this->source_type === 'printables';
    }

    /**
     * Get source icon name for display
     */
    public function getSourceIconAttribute(): string
    {
        return match ($this->source_type) {
            'aliexpress' => 'truck',
            'printables' => 'cube',
            default => 'tag',
        };
    }

    /**
     * Get source label for display
     */
    public function getSourceLabelAttribute(): string
    {
        return match ($this->source_type) {
            'aliexpress' => 'AliExpress',
            'printables' => 'Printables',
            'manual' => 'Manual',
            default => 'Unknown',
        };
    }

    // Methods

    /**
     * Calculate profit based on price and cost
     */
    public function calculateProfit(): void
    {
        $this->profit = $this->price - $this->cost;
        $this->save();
    }

    /**
     * Copy product data to this order item (snapshot)
     */
    public function snapshotFromProduct(Product $product): void
    {
        $this->product_id = $product->id;
        $this->title = $product->title;
        $this->sku = $product->sku;
        $this->cost = $product->cost_price ?? 0;
        $this->source_type = $product->source_type;
        $this->source_url = $product->source_url;
        $this->is_digital = $product->is_digital ?? false;
        $this->profit = $this->price - $this->cost;
        $this->save();
    }
}
