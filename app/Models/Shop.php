<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shop extends Model
{
    protected $fillable = [
        'name',
        'etsy_client_id',
        'etsy_client_secret',
        'etsy_shop_id',
        'etsy_user_id',
        'etsy_access_token',
        'etsy_refresh_token',
        'etsy_token_expires_at',
        'currency',
        'is_active',
        'auto_sync_enabled',
        'ai_title_prompt',
        'ai_description_prompt',
        'ai_image_prompt',
        'ai_image_enabled',
        'total_revenue',
        'total_orders',
    ];

    protected $casts = [
        'etsy_client_id' => 'encrypted',
        'etsy_client_secret' => 'encrypted',
        'etsy_token_expires_at' => 'datetime',
        'is_active' => 'boolean',
        'auto_sync_enabled' => 'boolean',
        'ai_image_enabled' => 'boolean',
        'total_revenue' => 'decimal:2',
        'total_orders' => 'integer',
    ];

    protected $hidden = [
        'etsy_client_id',
        'etsy_client_secret',
        'etsy_access_token',
        'etsy_refresh_token',
    ];

    // ============================================
    // RELATIONSHIPS
    // ============================================

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'shop_memberships')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function members(): HasMany
    {
        return $this->hasMany(ShopMembership::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    // ============================================
    // ACCESSORS
    // ============================================

    /**
     * Get the Etsy connection status.
     */
    public function getConnectionStatusAttribute(): string
    {
        if (!$this->etsy_shop_id) {
            return 'not_connected';
        }

        if ($this->etsy_token_expires_at && $this->etsy_token_expires_at->isPast()) {
            return 'expired';
        }

        return 'connected';
    }

    /**
     * Get the connection status label in French.
     */
    public function getConnectionStatusLabelAttribute(): string
    {
        return match ($this->connection_status) {
            'connected' => 'Connecté',
            'expired' => 'Expiré',
            'not_connected' => 'Non connecté',
            default => 'Inconnu',
        };
    }

    /**
     * Get the connection status color for UI.
     */
    public function getConnectionStatusColorAttribute(): string
    {
        return match ($this->connection_status) {
            'connected' => 'green',
            'expired' => 'red',
            'not_connected' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Check if shop is connected to Etsy.
     */
    public function isConnectedToEtsy(): bool
    {
        return $this->connection_status === 'connected';
    }

    /**
     * Get products count for this shop.
     */
    public function getProductsCountAttribute(): int
    {
        return $this->products()->count();
    }

    /**
     * Get orders count for this shop.
     */
    public function getOrdersCountAttribute(): int
    {
        return $this->orders()->count();
    }

    /**
     * Get today's orders count.
     */
    public function getTodaysOrdersCountAttribute(): int
    {
        return $this->orders()->whereDate('created_at', today())->count();
    }

    /**
     * Get today's revenue.
     */
    public function getTodaysRevenueAttribute(): float
    {
        return (float) $this->orders()
            ->whereDate('created_at', today())
            ->sum('total_price');
    }

    // ============================================
    // METHODS
    // ============================================

    /**
     * Update cached stats from orders.
     */
    public function updateCachedStats(): void
    {
        $this->update([
            'total_revenue' => $this->orders()->sum('total_price'),
            'total_orders' => $this->orders()->count(),
        ]);
    }

    /**
     * Get best selling product.
     */
    public function getBestSellingProduct(): ?Product
    {
        $bestSellingProductId = $this->orders()
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->whereNotNull('order_items.product_id')
            ->selectRaw('order_items.product_id, SUM(order_items.quantity) as total_sold')
            ->groupBy('order_items.product_id')
            ->orderByDesc('total_sold')
            ->value('product_id');

        return $bestSellingProductId ? Product::find($bestSellingProductId) : null;
    }

    /**
     * Get revenue data for chart (last N days).
     */
    public function getRevenueChartData(int $days = 30): array
    {
        $startDate = now()->subDays($days - 1)->startOfDay();
        
        $revenues = $this->orders()
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, SUM(total_price) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('revenue', 'date')
            ->toArray();

        $dates = [];
        $data = [];

        for ($i = 0; $i < $days; $i++) {
            $date = now()->subDays($days - 1 - $i)->format('Y-m-d');
            $dates[] = now()->subDays($days - 1 - $i)->format('d/m');
            $data[] = (float) ($revenues[$date] ?? 0);
        }

        return [
            'dates' => $dates,
            'data' => $data,
        ];
    }
}
