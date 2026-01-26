<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

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
            'description' => 'nullable|string|max:5000',
            'currency' => 'required|string|size:3',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('shop-logos', 'public');
            $validated['logo_path'] = $path;
        }

        // Remove 'logo' from validated as it's not a model field
        unset($validated['logo']);

        $shop = Shop::create($validated);

        // Attach current user as owner
        $shop->users()->attach(auth()->id(), ['role' => 'owner']);

        return redirect()->route('shops.show', $shop)
            ->with('success', 'Boutique creee avec succes !');
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
            'active_products' => $shop->products()->where('is_active', true)->count(),
            'total_orders' => $shop->orders()->count(),
            'total_revenue' => $shop->orders()->sum('total_price'),
            'total_profit' => $shop->orders()->sum('total_profit'),
            'today_orders' => $shop->orders()->today()->count(),
            'today_revenue' => $shop->orders()->today()->sum('total_price'),
            'this_month_orders' => $shop->orders()->thisMonth()->count(),
            'this_month_revenue' => $shop->orders()->thisMonth()->sum('total_price'),
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
            'description' => 'nullable|string|max:5000',
            'currency' => 'required|string|size:3',
            'is_active' => 'boolean',
            'ai_title_prompt' => 'nullable|string|max:5000',
            'ai_description_prompt' => 'nullable|string|max:5000',
            'ai_image_prompt' => 'nullable|string|max:5000',
            'ai_image_enabled' => 'boolean',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'default_margin_us' => 'nullable|numeric|min:1|max:10',
            'default_margin_other' => 'nullable|numeric|min:1|max:10',
        ]);

        // Handle unchecked checkboxes
        $validated['is_active'] = $request->boolean('is_active');
        $validated['ai_image_enabled'] = $request->boolean('ai_image_enabled');

        // Handle logo removal
        if ($request->boolean('remove_logo') && $shop->logo_path) {
            Storage::disk('public')->delete($shop->logo_path);
            $validated['logo_path'] = null;
        }

        // Handle logo upload
        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($shop->logo_path) {
                Storage::disk('public')->delete($shop->logo_path);
            }

            $path = $request->file('logo')->store('shop-logos', 'public');
            $validated['logo_path'] = $path;
        }

        // Remove 'logo' from validated as it's not a model field
        unset($validated['logo']);

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
            ->with('success', 'Boutique supprimee avec succes !');
    }

    /**
     * Switch active shop.
     */
    public function switch(Shop $shop)
    {
        Gate::authorize('view', $shop);

        session(['active_shop_id' => $shop->id]);

        return redirect()->back()
            ->with('success', "Boutique active changee vers : {$shop->name}");
    }

    /**
     * Update Etsy categories for a shop.
     */
    public function updateCategories(Request $request, Shop $shop)
    {
        Gate::authorize('update', $shop);

        $categoriesJson = $request->input('etsy_categories', '[]');
        $categories = json_decode($categoriesJson, true) ?? [];

        // Validate and clean categories
        $cleanedCategories = [];
        foreach ($categories as $cat) {
            if (! empty($cat['name']) || ! empty($cat['etsy_name'])) {
                $cleanedCategories[] = [
                    'name' => $cat['name'] ?? '',
                    'etsy_name' => $cat['etsy_name'] ?? '',
                    'etsy_id' => $cat['etsy_id'] ?? '',
                    'keywords' => $cat['keywords'] ?? '',
                ];
            }
        }

        $shop->update(['etsy_categories' => $cleanedCategories]);

        return redirect()->route('shops.edit', $shop)
            ->with('success', 'Categories Etsy mises a jour !');
    }

    /**
     * Update available tags for a shop.
     */
    public function updateTags(Request $request, Shop $shop)
    {
        Gate::authorize('update', $shop);

        $tagsJson = $request->input('available_tags', '[]');
        $tags = json_decode($tagsJson, true) ?? [];

        // Clean tags: lowercase, trim, remove empty, remove duplicates
        $cleanedTags = collect($tags)
            ->map(fn ($tag) => strtolower(trim($tag)))
            ->filter(fn ($tag) => ! empty($tag))
            ->unique()
            ->values()
            ->toArray();

        $shop->update(['available_tags' => $cleanedTags]);

        return redirect()->route('shops.edit', $shop)
            ->with('success', count($cleanedTags).' tags enregistres !');
    }
}
