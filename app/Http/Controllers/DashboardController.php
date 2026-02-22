<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        // Get all shops for the user with product count
        $shops = $user->shops()
            ->withCount('products')
            ->get();

        // Aggregate stats across all shops
        $shopIds = $shops->pluck('id');

        $totalStats = [
            'total_products' => Product::whereIn('shop_id', $shopIds)->count(),
        ];

        return view('dashboard', compact(
            'shops',
            'totalStats'
        ));
    }
}
