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

        // Get products needing attention
        // - Sync errors
        // - Not synced to Etsy (pending)
        // Note: No stock tracking for dropship (aliexpress) or digital (printables)
        //       Stock only matters for manual physical products
        $productsNeedingAttention = Product::whereIn('shop_id', $shopIds)
            ->where(function ($query) {
                $query->where('etsy_sync_status', 'error')
                    ->orWhere('etsy_sync_status', 'pending');
            })
            ->with('shop')
            ->orderByRaw("CASE 
                WHEN etsy_sync_status = 'error' THEN 1 
                WHEN etsy_sync_status = 'pending' THEN 2
                ELSE 3 
            END")
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
