<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Shop;
use App\Services\AliExpressScraperService;
use App\Services\ContentOptimizerService;
use App\Services\EtsyApiClient;
use App\Services\PrintablesScraperService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $shops = $user->shops()->get();

        if ($shops->isEmpty()) {
            return redirect()->route('dashboard')
                ->with('error', 'Veuillez d\'abord connecter une boutique.');
        }

        // Get selected shop or default to first/session
        $shopId = $request->get('shop_id', session('active_shop_id', $shops->first()->id));
        $shop = $shops->firstWhere('id', $shopId) ?? $shops->first();
        
        // Update session
        session(['active_shop_id' => $shop->id]);

        Gate::authorize('view', $shop);

        // Build query with filters
        $query = $shop->products();

        // Search filter
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Source type filter
        if ($sourceType = $request->get('source_type')) {
            $query->where('source_type', $sourceType);
        }

        // Sync status filter
        if ($syncStatus = $request->get('sync_status')) {
            $query->where('etsy_sync_status', $syncStatus);
        }

        // Active status filter
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Stats for the current shop
        $stats = [
            'total' => $shop->products()->count(),
            'synced' => $shop->products()->where('etsy_sync_status', 'synced')->count(),
            'pending' => $shop->products()->where('etsy_sync_status', 'pending')->count(),
            'errors' => $shop->products()->where('etsy_sync_status', 'error')->count(),
        ];

        $products = $query->latest()->paginate(24)->withQueryString();

        return view('products.index', compact('products', 'shop', 'shops', 'stats'));
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
            'low_stock_threshold' => 'nullable|integer|min:0|max:100',
            'is_digital' => 'boolean',
            'sku' => 'nullable|string|max:255',
            'aliexpress_url' => 'nullable|url',
            'is_active' => 'boolean',
            'auto_sync' => 'boolean',
        ]);

        $validated['shop_id'] = $shop->id;
        $validated['low_stock_threshold'] = $validated['low_stock_threshold'] ?? 5;
        $validated['is_digital'] = $validated['is_digital'] ?? false;

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
            'low_stock_threshold' => 'nullable|integer|min:0|max:100',
            'sku' => 'nullable|string|max:255',
            'aliexpress_url' => 'nullable|url',
            'is_active' => 'boolean',
            'auto_sync' => 'boolean',
        ]);

        $validated['low_stock_threshold'] = $validated['low_stock_threshold'] ?? 5;

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
     * Bulk sync products to Etsy.
     */
    public function bulkSync(Request $request)
    {
        $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'exists:products,id',
        ]);

        $products = Product::whereIn('id', $request->product_ids)->get();
        $synced = 0;
        $errors = [];

        foreach ($products as $product) {
            try {
                Gate::authorize('update', $product->shop);

                if (!$product->shop->etsy_shop_id) {
                    $errors[] = "{$product->title}: Boutique non connectée à Etsy";
                    continue;
                }

                $etsyClient = new EtsyApiClient($product->shop);

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
                    $etsyClient->updateListing(
                        $product->shop->etsy_shop_id,
                        $product->etsy_listing_id,
                        $listingData
                    );
                } else {
                    $response = $etsyClient->createListing($product->shop->etsy_shop_id, $listingData);
                    $product->etsy_listing_id = $response['listing_id'];
                }

                $product->update([
                    'etsy_sync_status' => 'synced',
                    'etsy_sync_error' => null,
                    'etsy_synced_at' => now(),
                ]);

                $synced++;
            } catch (\Exception $e) {
                $product->update([
                    'etsy_sync_status' => 'error',
                    'etsy_sync_error' => $e->getMessage(),
                ]);
                $errors[] = "{$product->title}: {$e->getMessage()}";
            }
        }

        $message = "{$synced} produit(s) synchronisé(s)";
        if (!empty($errors)) {
            $message .= ". " . count($errors) . " erreur(s).";
        }

        return response()->json([
            'success' => $synced > 0,
            'message' => $message,
            'synced' => $synced,
            'errors' => $errors,
        ]);
    }

    /**
     * Bulk delete products.
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'exists:products,id',
        ]);

        $products = Product::whereIn('id', $request->product_ids)->get();
        $deleted = 0;

        foreach ($products as $product) {
            try {
                Gate::authorize('delete', $product->shop);
                $product->delete();
                $deleted++;
            } catch (\Exception $e) {
                // Skip products user can't delete
            }
        }

        return response()->json([
            'success' => $deleted > 0,
            'message' => "{$deleted} produit(s) supprimé(s)",
            'deleted' => $deleted,
        ]);
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

    /**
     * Analyze AliExpress product URL and return optimized data.
     */
    public function analyzeAliExpress(Request $request)
    {
        $request->validate([
            'aliexpress_url' => 'required|url',
        ]);

        try {
            $scraper = new AliExpressScraperService();
            $optimizer = new ContentOptimizerService();

            // Validate URL
            if (!$scraper->isValidAliExpressUrl($request->aliexpress_url)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid AliExpress URL. Please provide a valid product link.',
                ], 400);
            }

            // Scrape product data
            $productData = $scraper->scrapeProduct($request->aliexpress_url);

            // Check if scraping got any useful data or hit CAPTCHA
            if (empty($productData['title']) || 
                stripos($productData['title'], 'captcha') !== false ||
                stripos($productData['title'], 'recaptcha') !== false ||
                stripos($productData['title'], 'verification') !== false) {
                return response()->json([
                    'success' => false,
                    'message' => 'AliExpress a bloque l\'extraction automatique (protection anti-bot). Passez en mode manuel: copiez le titre du produit depuis AliExpress, collez-le dans le champ titre, puis cliquez sur "Optimiser avec l\'IA".',
                    'use_manual' => true,
                    'tip' => 'Astuce: Copiez aussi le prix et les images manuellement depuis AliExpress.',
                ], 422);
            }

            // Optimize content for Etsy
            $optimizedTitle = $optimizer->optimizeTitle($productData['title']);
            $optimizedDescription = $optimizer->optimizeDescription(
                $productData['title'],
                $productData['description'],
                $productData['specs']
            );

            // Generate 13 SEO tags for Etsy
            $tags = $optimizer->generateTags($optimizedTitle, $optimizedDescription);

            // Calculate suggested price with markup
            $suggestedPrice = $productData['price']
                ? $optimizer->calculatePrice($productData['price'], 3)
                : null;

            return response()->json([
                'success' => true,
                'data' => [
                    'title' => $optimizedTitle,
                    'description' => $optimizedDescription,
                    'tags' => $tags,
                    'tags_string' => implode(', ', $tags),
                    'price' => $suggestedPrice,
                    'images' => $productData['images'],
                    'original_price' => $productData['price'],
                    'specs' => $productData['specs'],
                ],
                'message' => 'Product analyzed successfully! Data has been optimized for Etsy.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Analyze Printables product URL and return optimized data.
     */
    public function analyzePrintables(Request $request)
    {
        $request->validate([
            'printables_url' => 'required|url',
        ]);

        try {
            $scraper = new PrintablesScraperService();
            $optimizer = new ContentOptimizerService();

            // Validate URL
            if (!$scraper->isValidPrintablesUrl($request->printables_url)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Printables URL. Please provide a valid model link (e.g., https://www.printables.com/model/123456).',
                ], 400);
            }

            // Scrape product data
            $productData = $scraper->scrapeProduct($request->printables_url);

            // Check if scraping got any useful data
            if (empty($productData['title'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible d\'extraire les données du modèle Printables. Veuillez réessayer ou entrer les détails manuellement.',
                ], 422);
            }

            // Check license for commercial use
            $commercialAllowed = $scraper->isCommercialLicenseAllowed($productData['license'] ?? 'Unknown');

            // Optimize content for Etsy (3D printing context)
            $optimizedTitle = $optimizer->optimizeTitle($productData['title'], '3D Print');
            $optimizedDescription = $optimizer->optimizeDescription(
                $productData['title'],
                $productData['description'],
                [],
                true // is3DPrint flag
            );

            // Generate 13 SEO tags for Etsy (3D printing focused)
            $tags = $optimizer->generateTags($optimizedTitle, $optimizedDescription, true);

            // Generate attribution
            $attribution = $scraper->generateAttribution($productData);

            return response()->json([
                'success' => true,
                'data' => [
                    'title' => $optimizedTitle,
                    'description' => $optimizedDescription,
                    'tags' => $tags,
                    'tags_string' => implode(', ', $tags),
                    'images' => $productData['images'],
                    'author' => $productData['author'],
                    'license' => $productData['license'],
                    'commercial_allowed' => $commercialAllowed,
                    'attribution' => $attribution,
                    'source_url' => $productData['source_url'],
                    'downloads' => $productData['downloads'],
                    'likes' => $productData['likes'],
                ],
                'message' => 'Printables model analyzed successfully! Data has been optimized for Etsy.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Optimize product content manually (without scraping).
     */
    public function optimizeContent(Request $request)
    {
        $request->validate([
            'title' => 'required|string|min:3',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
        ]);

        try {
            $optimizer = new ContentOptimizerService();

            // Optimize content for Etsy
            $optimizedTitle = $optimizer->optimizeTitle($request->title);
            $optimizedDescription = $optimizer->optimizeDescription(
                $request->title,
                $request->description
            );

            // Generate 13 SEO tags for Etsy
            $tags = $optimizer->generateTags($optimizedTitle, $optimizedDescription);

            // Calculate suggested price with markup if price provided
            $suggestedPrice = $request->price
                ? $optimizer->calculatePrice(floatval($request->price), 3)
                : null;

            return response()->json([
                'success' => true,
                'data' => [
                    'title' => $optimizedTitle,
                    'description' => $optimizedDescription,
                    'tags' => $tags,
                    'tags_string' => implode(', ', $tags),
                    'price' => $suggestedPrice,
                    'original_price' => $request->price,
                ],
                'message' => 'Content optimized successfully for Etsy!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
