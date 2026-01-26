<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        // Get all shops for the user with eager loading
        $shops = $user->shops()
            ->withCount(['products', 'orders'])
            ->get();

        // Aggregate stats across all shops
        $shopIds = $shops->pluck('id');

        $totalStats = [
            'total_products' => Product::whereIn('shop_id', $shopIds)->count(),
            'total_orders' => Order::whereIn('shop_id', $shopIds)->count(),
            'total_revenue' => Order::whereIn('shop_id', $shopIds)->sum('total_price'),
            'total_profit' => Order::whereIn('shop_id', $shopIds)->sum('total_profit'),
            'today_orders' => Order::whereIn('shop_id', $shopIds)->today()->count(),
            'today_revenue' => Order::whereIn('shop_id', $shopIds)->today()->sum('total_price'),
        ];

        // Get today's orders (latest 10)
        $todaysOrders = Order::whereIn('shop_id', $shopIds)
            ->today()
            ->with(['shop', 'items'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // Get products needing attention (low stock or inactive)
        // Note: No stock tracking for dropship (aliexpress) or digital (printables)
        //       Stock only matters for manual physical products
        $productsNeedingAttention = Product::whereIn('shop_id', $shopIds)
            ->where(function ($query) {
                // Low stock products (not digital, not unlimited)
                $query->where('is_digital', false)
                    ->where('quantity', '<', 10)
                    ->where('quantity', '>', 0);
            })
            ->orWhere(function ($query) use ($shopIds) {
                // Out of stock products
                $query->whereIn('shop_id', $shopIds)
                    ->where('is_digital', false)
                    ->where('quantity', '<=', 0);
            })
            ->with('shop')
            ->orderBy('quantity', 'asc')
            ->limit(10)
            ->get();

        // Get new orders that need supplier ordering (dropship items)
        $ordersNeedingAction = Order::whereIn('shop_id', $shopIds)
            ->where('status', Order::STATUS_NEW)
            ->whereHas('items', function ($query) {
                $query->where('source_type', 'aliexpress');
            })
            ->with(['shop', 'items'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('dashboard', compact(
            'shops',
            'totalStats',
            'todaysOrders',
            'productsNeedingAttention',
            'ordersNeedingAction'
        ));
    }
}
