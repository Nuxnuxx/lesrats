<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Shop;
use App\Services\EtsyApiClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $shop = Shop::findOrFail(session('active_shop_id'));
        Gate::authorize('view', $shop);

        $products = $shop->products()->latest()->paginate(20);

        return view('products.index', compact('products', 'shop'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $shop = Shop::findOrFail(session('active_shop_id'));
        Gate::authorize('update', $shop);

        return view('products.create', compact('shop'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $shop = Shop::findOrFail(session('active_shop_id'));
        Gate::authorize('update', $shop);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:0',
            'sku' => 'nullable|string|max:255',
            'aliexpress_url' => 'nullable|url',
            'is_active' => 'boolean',
            'auto_sync' => 'boolean',
        ]);

        $validated['shop_id'] = $shop->id;

        $product = Product::create($validated);

        return redirect()->route('products.show', $product)
            ->with('success', 'Produit créé avec succès !');
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        Gate::authorize('view', $product->shop);

        return view('products.show', compact('product'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        Gate::authorize('update', $product->shop);

        return view('products.edit', compact('product'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        Gate::authorize('update', $product->shop);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:0',
            'sku' => 'nullable|string|max:255',
            'aliexpress_url' => 'nullable|url',
            'is_active' => 'boolean',
            'auto_sync' => 'boolean',
        ]);

        $product->update($validated);

        return redirect()->route('products.show', $product)
            ->with('success', 'Produit mis à jour avec succès !');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        Gate::authorize('delete', $product->shop);

        $product->delete();

        return redirect()->route('products.index')
            ->with('success', 'Produit supprimé avec succès !');
    }

    /**
     * Sync product to Etsy (create or update listing).
     */
    public function syncToEtsy(Product $product)
    {
        Gate::authorize('update', $product->shop);

        $shop = $product->shop;

        if (!$shop->etsy_shop_id) {
            return redirect()->back()
                ->with('error', 'La boutique n\'est pas connectée à Etsy.');
        }

        try {
            $etsyClient = new EtsyApiClient($shop);

            $listingData = [
                'title' => $product->title,
                'description' => $product->description ?? '',
                'price' => $product->price,
                'quantity' => $product->quantity,
                'who_made' => 'i_did',
                'when_made' => '2020_2023',
                'taxonomy_id' => 1,
            ];

            if ($product->etsy_listing_id) {
                // Update existing listing
                $response = $etsyClient->updateListing(
                    $shop->etsy_shop_id,
                    $product->etsy_listing_id,
                    $listingData
                );
            } else {
                // Create new listing
                $response = $etsyClient->createListing($shop->etsy_shop_id, $listingData);

                $product->update([
                    'etsy_listing_id' => $response['listing_id'],
                ]);
            }

            $product->update([
                'etsy_state' => $response['state'] ?? 'active',
                'etsy_synced_at' => now(),
            ]);

            return redirect()->back()
                ->with('success', 'Produit synchronisé avec Etsy avec succès !');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erreur lors de la synchronisation avec Etsy : ' . $e->getMessage());
        }
    }

    /**
     * Import products from Etsy.
     */
    public function importFromEtsy()
    {
        $shop = Shop::findOrFail(session('active_shop_id'));
        Gate::authorize('update', $shop);

        if (!$shop->etsy_shop_id) {
            return redirect()->back()
                ->with('error', 'La boutique n\'est pas connectée à Etsy.');
        }

        try {
            $etsyClient = new EtsyApiClient($shop);
            $listings = $etsyClient->getListings($shop->etsy_shop_id);

            $imported = 0;
            foreach ($listings['results'] ?? [] as $listing) {
                $product = Product::updateOrCreate(
                    [
                        'shop_id' => $shop->id,
                        'etsy_listing_id' => $listing['listing_id'],
                    ],
                    [
                        'title' => $listing['title'],
                        'description' => $listing['description'] ?? '',
                        'price' => $listing['price']['amount'] / $listing['price']['divisor'],
                        'quantity' => $listing['quantity'],
                        'sku' => $listing['sku'][0] ?? null,
                        'etsy_state' => $listing['state'],
                        'etsy_synced_at' => now(),
                        'is_active' => $listing['state'] === 'active',
                    ]
                );

                $imported++;
            }

            return redirect()->route('products.index')
                ->with('success', "{$imported} produit(s) importé(s) depuis Etsy !");

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erreur lors de l\'importation depuis Etsy : ' . $e->getMessage());
        }
    }
}
