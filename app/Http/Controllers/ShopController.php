<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ShopController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $shops = auth()->user()->shops()
            ->with('members')
            ->withCount(['products', 'orders'])
            ->get();

        return view('shops.index', compact('shops'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create', Shop::class);

        return view('shops.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Gate::authorize('create', Shop::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'currency' => 'required|string|size:3',
        ]);

        $shop = Shop::create($validated);

        // Attach current user as owner
        $shop->users()->attach(auth()->id(), ['role' => 'owner']);

        return redirect()->route('shops.index')
            ->with('success', 'Boutique créée avec succès !');
    }

    /**
     * Display the specified resource.
     */
    public function show(Shop $shop)
    {
        Gate::authorize('view', $shop);

        $shop->load('members.user');

        // Calculate stats
        $stats = [
            'total_products' => $shop->products()->count(),
            'total_orders' => $shop->orders()->count(),
            'total_revenue' => $shop->orders()->sum('total_price'),
            'total_profit' => $shop->orders()->sum('total_profit'),
            'today_orders' => $shop->orders()->today()->count(),
            'today_revenue' => $shop->orders()->today()->sum('total_price'),
            'this_month_orders' => $shop->orders()->thisMonth()->count(),
            'this_month_revenue' => $shop->orders()->thisMonth()->sum('total_price'),
            'pending_sync' => $shop->products()->where('etsy_sync_status', 'pending')->count(),
            'sync_errors' => $shop->products()->where('etsy_sync_status', 'error')->count(),
        ];

        // Get revenue chart data (last 30 days)
        $chartData = $shop->getRevenueChartData(30);

        // Get recent products (last 10)
        $recentProducts = $shop->products()
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // Get recent orders (last 10)
        $recentOrders = $shop->orders()
            ->with('items')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // Orders by status for quick stats
        $ordersByStatus = [
            'new' => $shop->orders()->where('status', Order::STATUS_NEW)->count(),
            'ordered' => $shop->orders()->where('status', Order::STATUS_ORDERED)->count(),
            'shipped' => $shop->orders()->where('status', Order::STATUS_SHIPPED)->count(),
            'delivered' => $shop->orders()->where('status', Order::STATUS_DELIVERED)->count(),
            'completed' => $shop->orders()->where('status', Order::STATUS_COMPLETED)->count(),
        ];

        return view('shops.show', compact(
            'shop',
            'stats',
            'chartData',
            'recentProducts',
            'recentOrders',
            'ordersByStatus'
        ));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Shop $shop)
    {
        Gate::authorize('update', $shop);

        return view('shops.edit', compact('shop'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Shop $shop)
    {
        Gate::authorize('update', $shop);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'mode' => 'required|in:manual,connected',
            'currency' => 'required|string|size:3',
            'is_active' => 'boolean',
            'auto_sync_enabled' => 'boolean',
            'ai_title_prompt' => 'nullable|string|max:2000',
            'ai_description_prompt' => 'nullable|string|max:2000',
            'ai_image_prompt' => 'nullable|string|max:2000',
            'ai_image_enabled' => 'boolean',
            'etsy_client_id' => 'nullable|string|max:500',
            'etsy_client_secret' => 'nullable|string|max:500',
        ]);

        // Validate mode: cannot switch to connected without Etsy credentials
        if ($validated['mode'] === 'connected' && !$shop->etsy_shop_id) {
            return redirect()->back()
                ->withErrors(['mode' => 'Vous devez connecter votre boutique Etsy pour activer le mode synchronise'])
                ->withInput();
        }

        // Handle unchecked checkboxes
        $validated['is_active'] = $request->boolean('is_active');
        $validated['auto_sync_enabled'] = $request->boolean('auto_sync_enabled');
        $validated['ai_image_enabled'] = $request->boolean('ai_image_enabled');

        // Only update Etsy credentials if new values provided (don't overwrite with empty)
        if (empty($validated['etsy_client_id'])) {
            unset($validated['etsy_client_id']);
        }
        if (empty($validated['etsy_client_secret'])) {
            unset($validated['etsy_client_secret']);
        }

        $shop->update($validated);

        return redirect()->route('shops.edit', $shop)
            ->with('success', 'Parametres mis a jour avec succes !');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Shop $shop)
    {
        Gate::authorize('delete', $shop);

        $shop->delete();

        return redirect()->route('shops.index')
            ->with('success', 'Boutique supprimée avec succès !');
    }

    /**
     * Switch active shop.
     */
    public function switch(Shop $shop)
    {
        Gate::authorize('view', $shop);

        session(['active_shop_id' => $shop->id]);

        return redirect()->back()
            ->with('success', "Boutique active changée vers : {$shop->name}");
    }
}
