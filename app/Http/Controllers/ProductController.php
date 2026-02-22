<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Shop;
use App\Services\AliExpressScraperService;
use App\Services\ContentOptimizerService;
use App\Services\FalImageService;
use App\Services\PrintablesScraperService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
                ->with('error', 'Veuillez d\'abord creer une boutique.');
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
                $q->where('title', 'like', "%{$search}%");
            });
        }

        // Source type filter
        if ($sourceType = $request->get('source_type')) {
            $query->where('source_type', $sourceType);
        }

        // Stats for the current shop
        $stats = [
            'total' => $shop->products()->count(),
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
            'aliexpress_url' => ['nullable', 'string', 'regex:/^https?:\/\//'],
        ]);

        $validated['shop_id'] = $shop->id;
        $validated['low_stock_threshold'] = $validated['low_stock_threshold'] ?? 5;
        $validated['is_digital'] = $validated['is_digital'] ?? false;

        // Auto-fill defaults for virtual shops
        if ($shop->product_type === 'virtual') {
            $validated['price'] = $validated['price'] ?: ($shop->default_price ?? 0);
            $validated['cost_price'] = 0;
            $validated['is_digital'] = true;
            $validated['quantity'] = 999;
        }

        $product = Product::create($validated);

        return redirect()->route('products.edit', $product)
            ->with('success', 'Produit cree avec succes !');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        Gate::authorize('update', $product->shop);

        // Get backgrounds from the shop for AI image generation
        $backgrounds = $product->shop->ai_backgrounds ?? [];

        return view('products.edit', compact('product', 'backgrounds'));
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
            'tags' => 'nullable|string',
            'etsy_category' => 'nullable|string|max:255',
            'price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'price_us' => 'nullable|numeric|min:0',
            'price_other' => 'nullable|numeric|min:0',

            'quantity' => 'required|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0|max:100',
            'source_url' => 'nullable|url',
            'aliexpress_url' => ['nullable', 'string', 'regex:/^https?:\/\//'],
            'sizes' => 'nullable|string',
        ]);

        $validated['low_stock_threshold'] = $validated['low_stock_threshold'] ?? 5;

        // Convert sizes JSON string to array
        if (isset($validated['sizes']) && is_string($validated['sizes'])) {
            $validated['sizes'] = json_decode($validated['sizes'], true) ?? [];
        }

        // Convert tags string to array
        if (isset($validated['tags']) && is_string($validated['tags'])) {
            $tagsArray = array_map('trim', explode(',', $validated['tags']));
            $tagsArray = array_filter($tagsArray); // Remove empty values
            $tagsArray = array_slice($tagsArray, 0, 13); // Limit to 13 tags for Etsy
            $validated['tags'] = $tagsArray;
        }

        $product->update($validated);

        return redirect()->route('products.edit', $product)
            ->with('success', 'Produit mis a jour avec succes !');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        Gate::authorize('delete', $product->shop);

        $product->delete();

        return redirect()->route('products.index')
            ->with('success', 'Produit supprime avec succes !');
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
            'message' => "{$deleted} produit(s) supprime(s)",
            'deleted' => $deleted,
        ]);
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
            $scraper = new AliExpressScraperService;
            $optimizer = new ContentOptimizerService;

            // Validate URL
            if (! $scraper->isValidAliExpressUrl($request->aliexpress_url)) {
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

            // Optimize content
            $optimizedTitle = $optimizer->optimizeTitle($productData['title']);
            $optimizedDescription = $optimizer->optimizeDescription(
                $productData['title'],
                $productData['description'],
                $productData['specs']
            );

            // Generate SEO tags
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
                'message' => 'Product analyzed successfully!',
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
            $scraper = new PrintablesScraperService;
            $optimizer = new ContentOptimizerService;

            // Validate URL
            if (! $scraper->isValidPrintablesUrl($request->printables_url)) {
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
                    'message' => 'Impossible d\'extraire les donnees du modele Printables. Veuillez reessayer ou entrer les details manuellement.',
                ], 422);
            }

            // Check license for commercial use
            $commercialAllowed = $scraper->isCommercialLicenseAllowed($productData['license'] ?? 'Unknown');

            // Optimize content (3D printing context)
            $optimizedTitle = $optimizer->optimizeTitle($productData['title'], '3D Print');
            $optimizedDescription = $optimizer->optimizeDescription(
                $productData['title'],
                $productData['description'],
                [],
                true // is3DPrint flag
            );

            // Generate SEO tags (3D printing focused)
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
                'message' => 'Printables model analyzed successfully!',
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
            $optimizer = new ContentOptimizerService;

            // Optimize content
            $optimizedTitle = $optimizer->optimizeTitle($request->title);
            $optimizedDescription = $optimizer->optimizeDescription(
                $request->title,
                $request->description
            );

            // Generate SEO tags
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
                'message' => 'Content optimized successfully!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate AI images for a product using Fal.ai
     */
    public function generateAiImages(Request $request, Product $product)
    {
        Gate::authorize('update', $product->shop);

        $shop = $product->shop;
        $user = $request->user();

        // Check if AI image generation is configured
        if (empty($shop->ai_image_prompt)) {
            return redirect()->back()
                ->with('error', 'Aucun prompt d\'image IA configure. Configurez le prompt dans les parametres de la boutique.');
        }

        // Get current images
        $images = is_string($product->images) ? json_decode($product->images, true) : $product->images;

        if (empty($images)) {
            return redirect()->back()
                ->with('error', 'Ce produit n\'a pas d\'images a transformer.');
        }

        try {
            // Get Fal.ai API key
            $falApiKey = $user?->fal_api_key ?? config('services.fal.api_key');

            if (empty($falApiKey)) {
                return redirect()->back()
                    ->with('error', 'Cle API Fal.ai non configuree. Ajoutez-la dans votre profil ou dans la configuration.');
            }

            $falService = new FalImageService($falApiKey);
            $transformedImages = [];
            $successCount = 0;

            // Transform each image (limit to 5)
            foreach (array_slice($images, 0, 5) as $imageUrl) {
                $transformedPath = $falService->transformImage($imageUrl, $shop->ai_image_prompt);
                if ($transformedPath) {
                    if ($product->apply_logo && $shop->logo_path) {
                        $falService->applyLogoOverlay($transformedPath, $shop->logo_path);
                    }
                    $transformedImages[] = $transformedPath;
                    $successCount++;
                } else {
                    // Keep original image if transformation fails
                    $transformedImages[] = $imageUrl;
                }
            }

            // Add remaining untransformed images
            if (count($images) > 5) {
                $transformedImages = array_merge($transformedImages, array_slice($images, 5));
            }

            // Update product with new images
            $product->update(['images' => $transformedImages]);

            if ($successCount > 0) {
                return redirect()->back()
                    ->with('success', "{$successCount} image(s) transformee(s) avec succes par l'IA!");
            } else {
                return redirect()->back()
                    ->with('error', 'Aucune image n\'a pu etre transformee. Verifiez les logs pour plus de details.');
            }

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erreur lors de la generation des images: '.$e->getMessage());
        }
    }

    /**
     * Transform a single image with AI and add to real_images.
     */
    public function transformSingleImage(Request $request, Product $product)
    {
        Gate::authorize('update', $product->shop);

        $request->validate([
            'image_url' => 'required|string',
            'prompt' => 'required|string|min:10',
            'background_url' => 'nullable|string',
            'apply_logo' => 'nullable|boolean',
        ]);

        $user = $request->user();

        try {
            // Get Fal.ai API key
            $falApiKey = $user?->fal_api_key ?? config('services.fal.api_key');

            if (empty($falApiKey)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cle API Fal.ai non configuree. Ajoutez-la dans votre profil ou dans la configuration.',
                ], 400);
            }

            $falService = new FalImageService($falApiKey);
            $backgroundUrl = $request->input('background_url');

            // Transform the image with optional background
            $transformedPath = $falService->transformImage(
                $request->image_url,
                $request->prompt,
                0.65, // Fixed strength
                $backgroundUrl
            );

            if (! $transformedPath) {
                return response()->json([
                    'success' => false,
                    'message' => 'La transformation de l\'image a echoue. Verifiez les logs pour plus de details.',
                ], 500);
            }

            // Apply shop logo overlay if requested for this generation
            Log::info('transformSingleImage: apply_logo check', [
                'apply_logo_raw' => $request->input('apply_logo'),
                'apply_logo_bool' => $request->boolean('apply_logo', false),
                'logo_path' => $product->shop->logo_path,
                'transformed_path' => $transformedPath,
            ]);
            if ($request->boolean('apply_logo', false) && $product->shop->logo_path) {
                $falService->applyLogoOverlay($transformedPath, $product->shop->logo_path);
            }

            // Add to real_images array
            $realImages = $product->real_images ?? [];
            $realImages[] = $transformedPath;
            $product->update(['real_images' => $realImages]);

            // Remember last used background on the shop
            $shop = $product->shop;
            if ($backgroundUrl) {
                $bgPath = preg_replace('#^https?://[^/]+/storage/#', '', $backgroundUrl);
                $shop->update(['default_ai_background' => $bgPath !== $backgroundUrl ? $bgPath : null]);
            } else {
                $shop->update(['default_ai_background' => null]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Image transformee et ajoutee aux images reelles!',
                'data' => [
                    'transformed_image' => $transformedPath,
                    'real_images' => $realImages,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la transformation: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle logo overlay on AI-generated images.
     */
    public function toggleLogo(Request $request, Product $product)
    {
        Gate::authorize('update', $product->shop);

        $request->validate(['apply_logo' => 'required|boolean']);

        $product->update(['apply_logo' => $request->boolean('apply_logo')]);

        return response()->json(['success' => true, 'apply_logo' => $product->apply_logo]);
    }

    /**
     * DEBUG TEMP: Download first AliExpress image and apply logo overlay to test the library.
     */
    public function debugApplyLogo(Request $request, Product $product)
    {
        Gate::authorize('update', $product->shop);

        if (! $product->shop->logo_path) {
            return response()->json(['success' => false, 'message' => 'Pas de logo configuré dans la boutique']);
        }

        $images = $product->images ?? [];
        if (empty($images)) {
            return response()->json(['success' => false, 'message' => 'Pas d\'images sur ce produit']);
        }

        // Download first image to a local debug folder
        $sourceUrl = $images[0];
        $httpResponse = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Referer' => 'https://www.aliexpress.com/',
            'Accept' => 'image/webp,image/apng,image/*,*/*;q=0.8',
        ])->withOptions(['verify' => false])->get($sourceUrl);
        if (! $httpResponse->successful()) {
            return response()->json(['success' => false, 'message' => "Impossible de télécharger l'image: HTTP {$httpResponse->status()}"]);
        }

        $ext = pathinfo(parse_url($sourceUrl, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
        $filename = 'debug-logo-test/'.Str::random(16).'.'.$ext;
        $disk = Storage::disk('public');
        $disk->put($filename, $httpResponse->body());
        $imageUrl = $disk->url($filename);

        Log::info('debugApplyLogo: image downloaded', ['url' => $imageUrl, 'filename' => $filename]);

        $falApiKey = $request->user()?->fal_api_key ?? config('services.fal.api_key');
        $falService = new FalImageService($falApiKey);
        $result = $falService->applyLogoOverlay($imageUrl, $product->shop->logo_path);

        if ($result) {
            return response()->file($disk->path($filename));
        }

        return response('Échec — vérifier storage/logs/laravel.log', 500);
    }

    /**
     * Remove an image from real_images.
     */
    public function removeRealImage(Request $request, Product $product)
    {
        Gate::authorize('update', $product->shop);

        $request->validate([
            'image_index' => 'required|integer|min:0',
        ]);

        $realImages = $product->real_images ?? [];
        $index = $request->input('image_index');

        if (! isset($realImages[$index])) {
            return response()->json([
                'success' => false,
                'message' => 'Image non trouvee.',
            ], 404);
        }

        // Remove image at index
        array_splice($realImages, $index, 1);
        $product->update(['real_images' => $realImages]);

        return response()->json([
            'success' => true,
            'message' => 'Image supprimee des images reelles.',
            'data' => [
                'real_images' => $realImages,
            ],
        ]);
    }

    /**
     * Remove an image from the product's source images.
     */
    public function removeImage(Request $request, Product $product)
    {
        Gate::authorize('update', $product->shop);

        $request->validate([
            'image_index' => 'required|integer|min:0',
        ]);

        $images = $product->images ?? [];
        $index = $request->input('image_index');

        if (! isset($images[$index])) {
            return response()->json([
                'success' => false,
                'message' => 'Image non trouvee.',
            ], 404);
        }

        array_splice($images, $index, 1);
        $product->update(['images' => $images]);

        return response()->json([
            'success' => true,
            'message' => 'Image supprimee.',
            'data' => [
                'images' => $images,
            ],
        ]);
    }

    /**
     * Download product images as a ZIP file.
     */
    public function downloadImages(Product $product)
    {
        Gate::authorize('view', $product->shop);

        // Use real_images if available, otherwise fall back to original images
        $images = ! empty($product->real_images) ? $product->real_images : ($product->images ?? []);

        if (empty($images)) {
            return back()->with('error', 'Aucune image a telecharger.');
        }

        $slug = Str::slug($product->title) ?: 'produit';
        $zipPath = tempnam(sys_get_temp_dir(), 'zip_').'.zip';

        $zip = new \ZipArchive;
        if ($zip->open($zipPath, \ZipArchive::CREATE) !== true) {
            return back()->with('error', 'Impossible de creer le fichier ZIP.');
        }

        foreach ($images as $index => $imageUrl) {
            try {
                $response = Http::timeout(30)->get($imageUrl);

                if (! $response->successful()) {
                    continue;
                }

                $ext = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'png';
                $filename = str_pad($index + 1, 2, '0', STR_PAD_LEFT).'.'.$ext;
                $zip->addFromString($filename, $response->body());
            } catch (\Exception $e) {
                continue;
            }
        }

        $zip->close();

        return response()->download($zipPath, $slug.'.zip')->deleteFileAfterSend(true);
    }
}
