<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Shop;
use App\Services\ContentOptimizerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ExtensionController extends Controller
{
    /**
     * Import a product from the browser extension.
     */
    public function import(Request $request)
    {
        // Log de la requête pour debug
        Log::info('Extension import request', [
            'data' => $request->all(),
            'headers' => $request->headers->all(),
        ]);

        // Validation des données
        $validated = $request->validate([
            'title' => 'required|string|max:500',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'images' => 'nullable|array',
            'images.*' => 'nullable|string|url',
            'variants' => 'nullable|array',
            'variants.*.name' => 'nullable|string',
            'variants.*.values' => 'nullable|array',
            'variants.*.values.*' => 'nullable|string',
            'specifications' => 'nullable|array',
            'source_url' => 'required|string|url',
            'aliexpress_product_id' => 'nullable|string',
            'source_type' => 'nullable|string|in:aliexpress,printables,manual',
            'shop_id' => 'nullable|integer|exists:shops,id',
            // Country-specific pricing data
            'country_prices' => 'nullable|array',
            'country_prices.*.price' => 'nullable|numeric|min:0',
            'country_prices.*.shipping' => 'nullable|numeric|min:0',
            'country_prices.*.total' => 'nullable|numeric|min:0',
        ]);

        try {
            $user = $request->user();

            // Trouver la boutique
            $shop = null;

            // Si un shop_id est fourni, vérifier que l'utilisateur y a accès
            if (! empty($validated['shop_id'])) {
                $shop = $user->shops()->where('shops.id', $validated['shop_id'])->first();
                if (! $shop) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Vous n\'avez pas accès à cette boutique.',
                    ], 403);
                }
            }

            // Sinon, utiliser la première boutique de l'utilisateur
            if (! $shop) {
                $shop = $user->shops()->first();
            }

            if (! $shop) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune boutique trouvée. Créez une boutique d\'abord.',
                ], 400);
            }

            // Vérifier si le produit existe déjà (par aliexpress_product_id ou source_url)
            $existingProduct = null;
            if (! empty($validated['aliexpress_product_id'])) {
                $existingProduct = Product::where('shop_id', $shop->id)
                    ->where('aliexpress_product_id', $validated['aliexpress_product_id'])
                    ->first();
            }

            if (! $existingProduct && ! empty($validated['source_url'])) {
                $existingProduct = Product::where('shop_id', $shop->id)
                    ->where('source_url', $validated['source_url'])
                    ->first();
            }

            if ($existingProduct) {
                // Update sizes if variants are provided and product has no sizes yet
                if (! empty($validated['variants']) && empty($existingProduct->sizes)) {
                    $sizes = collect($validated['variants'])
                        ->filter(fn ($v) => stripos($v['name'] ?? '', 'size') !== false || stripos($v['name'] ?? '', 'taille') !== false)
                        ->flatMap(fn ($v) => $v['values'] ?? [])
                        ->unique()->values()->toArray();
                    if (! empty($sizes)) {
                        $existingProduct->update(['sizes' => $sizes]);
                        Log::info('Updated sizes on existing product', ['product_id' => $existingProduct->id, 'sizes' => $sizes]);
                    }
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Produit déjà importé',
                    'product_id' => $existingProduct->id,
                    'product_url' => route('products.edit', $existingProduct),
                    'is_existing' => true,
                ]);
            }

            // Calculer le prix de vente
            $sourceType = $validated['source_type'] ?? 'aliexpress';
            $countryPrices = $validated['country_prices'] ?? null;
            $priceUs = null;
            $priceOther = null;

            if ($sourceType === 'printables' || $shop->product_type === 'virtual') {
                // Virtual/digital product — use shop default price
                $costPrice = 0;
                $defaultPrice = (float) ($shop->default_price ?? 5.99);
                $sellingPrice = $defaultPrice;
                $priceUs = $defaultPrice;
                $priceOther = $defaultPrice;
                $countryPrices = null; // Pas de prix par pays pour les produits numériques

                Log::info('Virtual product - default price applied', [
                    'selling_price' => $sellingPrice,
                    'shop_default_price' => $shop->default_price,
                ]);
            } else {
                // AliExpress product: expert mode formula or default ×2.5 markup
                $costPrice = $validated['price'] ?? 0;
                $useExpertPricing = $shop->expert_mode
                    && $shop->pricing_k > 0
                    && $shop->pricing_t < 1;

                // Popt = 1/k + (T0 + C) / (1 - T)
                $applyFormula = function (float $cost) use ($shop): float {
                    return round(
                        (1 / $shop->pricing_k) + ((float) $shop->pricing_t0 + $cost) / (1 - (float) $shop->pricing_t),
                        2
                    );
                };

                $sellingPrice = $costPrice > 0
                    ? ($useExpertPricing ? $applyFormula($costPrice) : round($costPrice * 2.5, 2))
                    : 0;

                // Process country-specific pricing if available
                if ($countryPrices && ! empty($countryPrices)) {
                    // Calculate US price
                    if (isset($countryPrices['US']['total'])) {
                        $costUs = (float) $countryPrices['US']['total'];
                        $priceUs = $useExpertPricing
                            ? $applyFormula($costUs)
                            : round($costUs * 2.5, 2);
                    }

                    // Calculate "Autres pays" price (highest of DE, AT, FR, CA, ES)
                    $otherCountries = ['DE', 'AT', 'FR', 'CA', 'ES'];
                    $maxOtherTotal = null;
                    foreach ($otherCountries as $country) {
                        if (isset($countryPrices[$country]['total'])) {
                            $total = (float) $countryPrices[$country]['total'];
                            if ($maxOtherTotal === null || $total > $maxOtherTotal) {
                                $maxOtherTotal = $total;
                            }
                        }
                    }
                    if ($maxOtherTotal !== null) {
                        $priceOther = $useExpertPricing
                            ? $applyFormula($maxOtherTotal)
                            : round($maxOtherTotal * 2.5, 2);
                    }

                    Log::info('Country prices processed', [
                        'expert_mode' => $useExpertPricing,
                        'country_prices' => $countryPrices,
                        'price_us' => $priceUs,
                        'price_other' => $priceOther,
                    ]);
                }
            }

            // Récupérer les prompts personnalisés de la boutique (avec niche injectée)
            $descriptionPrompt = $shop->getEffectiveAiDescriptionPrompt();

            // Optimiser le titre et la description avec l'IA
            $originalTitle = $validated['title'];
            $originalDescription = $validated['description'] ?? '';
            $is3DPrint = ($validated['source_type'] ?? 'aliexpress') === 'printables';

            try {
                $optimizer = new ContentOptimizerService;

                // Analyser les images avec Groq Vision pour extraire couleur, matière, motif
                $visualContext = null;
                if (! empty($validated['images'])) {
                    $visualContext = $optimizer->analyzeProductImages($validated['images']);
                    Log::info('Visual context from image analysis', ['context' => $visualContext]);
                }

                // Optimiser le titre (traduction en anglais + SEO) avec le prompt personnalisé
                $optimizedTitle = $optimizer->optimizeTitle(
                    $originalTitle,
                    $is3DPrint ? '3D Print' : null,
                    $descriptionPrompt,
                    $visualContext
                );
                Log::info('Optimized title', ['original' => $originalTitle, 'optimized' => $optimizedTitle]);

                // Générer une description optimisée avec le prompt personnalisé
                $description = $optimizer->optimizeDescription(
                    $originalTitle,
                    $originalDescription,
                    $validated['specifications'] ?? [],
                    $is3DPrint,
                    $descriptionPrompt,
                    $visualContext
                );
                Log::info('Generated description from title', ['title' => $originalTitle]);

                // Générer les tags (utilise la liste de tags de la boutique si disponible)
                $tags = $optimizer->selectRelevantTags(
                    $optimizedTitle,
                    $description,
                    $shop->available_tags ?? [],
                    $is3DPrint,
                    $visualContext
                );
                Log::info('Generated tags', ['count' => count($tags), 'tags' => $tags]);

                // Sélectionner la catégorie Etsy (si des catégories sont définies)
                $etsyCategory = $optimizer->selectCategory(
                    $optimizedTitle,
                    $description,
                    $shop->etsy_categories ?? []
                );
                Log::info('Selected category', ['category' => $etsyCategory]);

                // Analyser les attributs produit (couleur, matériaux, poches)
                $productAttributes = $optimizer->analyzeProductAttributes(
                    $optimizedTitle,
                    $visualContext,
                    $is3DPrint
                );
                Log::info('Product attributes analyzed', $productAttributes);
            } catch (\Exception $e) {
                Log::error('Failed to optimize content', ['error' => $e->getMessage()]);
                $optimizedTitle = $originalTitle;
                $description = $originalDescription;
                $tags = [];
                $etsyCategory = null;
                $productAttributes = ['main_color' => null, 'secondary_color' => null, 'materials' => [], 'has_pockets' => null];
            }

            // Images are kept as-is during import
            // AI image generation can be triggered manually from the product edit page
            $images = $validated['images'] ?? [];

            // Deduplicate Printables images (same image at different sizes/formats)
            if ($sourceType === 'printables' && ! empty($images)) {
                $seen = [];
                $unique = [];
                foreach ($images as $imageUrl) {
                    $path = parse_url($imageUrl, PHP_URL_PATH);
                    if (! $path) {
                        continue;
                    }
                    // Extract unique Printables image identifier: /images/{id}_{uuid}/
                    if (preg_match('/images\/(\d+_[a-f0-9-]+)/', $path, $matches)) {
                        $identifier = $matches[1];
                    } else {
                        $identifier = pathinfo($path, PATHINFO_FILENAME);
                    }
                    if (! isset($seen[$identifier])) {
                        $seen[$identifier] = true;
                        $unique[] = $imageUrl;
                    }
                }
                $images = array_slice($unique, 0, 10);

                Log::info('Printables images deduplicated', [
                    'before' => count($validated['images'] ?? []),
                    'after' => count($images),
                ]);
            }

            // Déterminer le stock et les paramètres selon le type de source
            // $sourceType is already defined above
            $isDigital = $sourceType === 'printables';

            if ($isDigital) {
                // Produit digital (STL) = stock illimité
                $quantity = 999;
                $lowStockThreshold = 5;
            } else {
                // Produit physique (AliExpress) = stock limité par défaut
                $quantity = 5;
                $lowStockThreshold = 1;
            }

            // Créer le produit
            $product = Product::create([
                'shop_id' => $shop->id,
                'title' => $optimizedTitle,
                'description' => $description,
                'price' => $sellingPrice,
                'cost_price' => $costPrice,
                'country_prices' => $countryPrices,
                'price_us' => $priceUs,
                'price_other' => $priceOther,
                'images' => $images,
                'tags' => $tags ?? [],
                'etsy_category' => $etsyCategory ?? null,
                'source_url' => $validated['source_url'],
                'source_type' => $sourceType,
                'aliexpress_product_id' => $validated['aliexpress_product_id'] ?? null,
                'aliexpress_url' => $validated['source_url'],
                'quantity' => $quantity,
                'is_digital' => $isDigital,
                'low_stock_threshold' => $lowStockThreshold,
                'main_color' => $productAttributes['main_color'] ?? null,
                'secondary_color' => $productAttributes['secondary_color'] ?? null,
                'materials' => $productAttributes['materials'] ?? [],
                'has_pockets' => $productAttributes['has_pockets'] ?? null,
            ]);

            // Extract sizes from variants (AliExpress sends variants with name/values)
            Log::info('Variants received', ['variants' => $validated['variants'] ?? null]);
            if (! empty($validated['variants'])) {
                $sizes = collect($validated['variants'])
                    ->filter(fn ($v) => stripos($v['name'] ?? '', 'size') !== false || stripos($v['name'] ?? '', 'taille') !== false)
                    ->flatMap(fn ($v) => $v['values'] ?? [])
                    ->unique()->values()->toArray();
                if (! empty($sizes)) {
                    $product->update(['sizes' => $sizes]);
                }
            }

            Log::info('Product imported via extension', [
                'product_id' => $product->id,
                'title' => $product->title,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Produit importé avec succès!',
                'product_id' => $product->id,
                'product_url' => route('products.edit', $product),
                'is_existing' => false,
            ]);
        } catch (\Exception $e) {
            Log::error('Extension import error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'import: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check extension connection / health check.
     */
    public function ping()
    {
        return response()->json([
            'success' => true,
            'message' => 'LesRats API is running',
            'version' => '1.0.0',
        ]);
    }

    /**
     * Get list of shops for extension dropdown.
     * Only returns shops the authenticated user has access to.
     */
    public function getShops(Request $request)
    {
        try {
            // Get only shops the user has access to
            $shops = $request->user()->shops()->select('shops.id', 'shops.name')->get();

            return response()->json([
                'success' => true,
                'shops' => $shops->map(function ($shop) {
                    return [
                        'id' => $shop->id,
                        'name' => $shop->name,
                        'platform' => null, // Platform column doesn't exist yet
                    ];
                }),
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching shops', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching shops: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get product data formatted for Etsy publishing.
     */
    public function getEtsyData(Request $request, $id)
    {
        try {
            $user = $request->user();
            $product = Product::with('shop')->findOrFail($id);

            // Verify the user has access to this product's shop
            if (! $user->hasAccessToShop($product->shop)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'avez pas accès à ce produit.',
                ], 403);
            }

            // Get tags as array
            $tags = $product->tags;
            if (is_string($tags)) {
                $tags = json_decode($tags, true) ?? [];
            }
            if (! is_array($tags)) {
                $tags = [];
            }

            // Ensure we have exactly 13 tags (Etsy max)
            $tags = array_slice($tags, 0, 13);

            // Get images as array - prefer real_images (AI generated) over original images
            $realImages = $product->real_images;
            if (is_string($realImages)) {
                $realImages = json_decode($realImages, true) ?? [];
            }
            if (! is_array($realImages)) {
                $realImages = [];
            }

            // Fall back to original images if no real_images
            $images = $product->images;
            if (is_string($images)) {
                $images = json_decode($images, true) ?? [];
            }
            if (! is_array($images)) {
                $images = [];
            }

            // Use real_images if available, otherwise use original images
            $imagesToUse = ! empty($realImages) ? $realImages : $images;

            // Resolve storage-relative paths to full URLs (needed by browser extension for chrome.downloads)
            $imagesToUse = array_map([Product::class, 'resolveImageUrl'], $imagesToUse);

            // Get category name — categories are now simple strings
            $categoryName = $product->etsy_category;
            if ($categoryName && $product->shop->etsy_categories) {
                $categories = array_map(fn ($cat) => is_array($cat) ? ($cat['name'] ?? '') : (string) $cat, $product->shop->etsy_categories);
                if (! in_array($categoryName, $categories)) {
                    $categoryName = null;
                }
            }

            // Inflate prices if shop has an active discount (so Etsy shows the right final price after discount)
            $discount = (float) ($product->shop->discount_percentage ?? 0);
            $factor = $discount > 0 ? (1 / (1 - $discount / 100)) : 1;

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $product->id,
                    'title' => $product->title,
                    'description' => $product->description,
                    'price' => round((float) $product->price * $factor, 2),
                    'price_us' => $product->price_us ? round((float) $product->price_us * $factor, 2) : null,
                    'price_other' => $product->price_other ? round((float) $product->price_other * $factor, 2) : null,
                    'tags' => $tags,
                    'images' => $imagesToUse,
                    'sizes' => $product->sizes ?? [],
                    'size_type' => $this->detectSizeType($product->sizes ?? []),
                    'quantity' => $product->quantity ?? 999,
                    'is_digital' => (bool) $product->is_digital,
                    'shop_name' => $product->shop->name,
                    'shop_id' => $product->shop->id,
                    'etsy_category' => $categoryName,
                    'main_color' => $product->main_color,
                    'secondary_color' => $product->secondary_color,
                    'materials' => $product->materials ?? [],
                    'has_pockets' => $product->has_pockets,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching Etsy data', [
                'product_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Product not found or error: '.$e->getMessage(),
            ], 404);
        }
    }

    /**
     * Suggest the best shop for a product using AI.
     */
    public function suggestShop(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:500',
            'description' => 'nullable|string|max:2000',
            'price' => 'nullable|numeric|min:0',
            'source_type' => 'nullable|string|in:aliexpress,printables',
        ]);

        try {
            $user = $request->user();
            $shops = $user->shops()->select('shops.id', 'shops.name', 'shops.description', 'shops.product_type')->get();

            if ($shops->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'Aucune boutique trouvee.'], 400);
            }

            if ($shops->count() === 1) {
                $shop = $shops->first();

                return response()->json([
                    'success' => true,
                    'shop_id' => $shop->id,
                    'shop_name' => $shop->name,
                ]);
            }

            $sourceType = $validated['source_type'] ?? null;

            // Fast-path: match source_type to product_type without AI
            if ($sourceType === 'printables') {
                $digitalShops = $shops->whereIn('product_type', ['digital', 'virtual']);
                if ($digitalShops->count() === 1) {
                    $shop = $digitalShops->first();

                    return response()->json([
                        'success' => true,
                        'shop_id' => $shop->id,
                        'shop_name' => $shop->name,
                        'method' => 'product_type_match',
                    ]);
                }
            } elseif ($sourceType === 'aliexpress') {
                $physicalShops = $shops->where('product_type', 'physical');
                if ($physicalShops->count() === 1) {
                    $shop = $physicalShops->first();

                    return response()->json([
                        'success' => true,
                        'shop_id' => $shop->id,
                        'shop_name' => $shop->name,
                        'method' => 'product_type_match',
                    ]);
                }
            }

            // Build shop list for AI prompt (include product_type for context)
            $shopList = $shops->map(fn ($s) => "- ID:{$s->id} | {$s->name} | type:{$s->product_type} | {$s->description}")->implode("\n");

            $productTitle = $validated['title'];
            $productDesc = mb_substr($validated['description'] ?? '', 0, 500);

            $sourceInfo = '';
            if ($sourceType === 'printables') {
                $sourceInfo = "Source: Printables.com (3D printing model / STL file)\n";
            } elseif ($sourceType === 'aliexpress') {
                $sourceInfo = "Source: AliExpress (physical product from China)\n";
            }

            $prompt = "Given this product:\n"
                .$sourceInfo
                ."Title: {$productTitle}\n"
                .($productDesc ? "Description: {$productDesc}\n" : '')
                ."\nWhich shop is the best fit? Pick EXACTLY ONE shop ID from this list:\n"
                .$shopList
                ."\n\nOutput ONLY the shop ID number, nothing else.";

            $systemPrompt = 'You are a shop-matching expert. Match products to the most relevant shop based on niche and description. '
                .'Output only the numeric shop ID, no explanations.';

            $apiKey = config('services.groq.api_key');
            $model = config('services.groq.model', 'llama-3.3-70b-versatile');

            if (! $apiKey) {
                // Fallback: return first shop
                $shop = $shops->first();

                return response()->json([
                    'success' => true,
                    'shop_id' => $shop->id,
                    'shop_name' => $shop->name,
                    'method' => 'fallback',
                ]);
            }

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(15)->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => 20,
                'temperature' => 0.2,
            ]);

            if ($response->successful()) {
                $result = $response->json();
                $suggestedId = trim($result['choices'][0]['message']['content'] ?? '');
                // Extract numeric ID
                preg_match('/(\d+)/', $suggestedId, $matches);
                $shopId = $matches[1] ?? null;

                $matchedShop = $shops->firstWhere('id', (int) $shopId);
                if ($matchedShop) {
                    Log::info('AI suggested shop', ['shop_id' => $matchedShop->id, 'shop_name' => $matchedShop->name, 'product' => $productTitle]);

                    return response()->json([
                        'success' => true,
                        'shop_id' => $matchedShop->id,
                        'shop_name' => $matchedShop->name,
                        'method' => 'ai',
                    ]);
                }
            }

            // Fallback: keyword matching
            $text = strtolower($productTitle.' '.($productDesc ?? ''));
            $bestShop = null;
            $bestScore = 0;

            foreach ($shops as $shop) {
                $score = 0;
                $shopDesc = strtolower($shop->name.' '.($shop->description ?? ''));
                $words = array_filter(explode(' ', $shopDesc), fn ($w) => strlen($w) > 2);
                foreach ($words as $word) {
                    if (str_contains($text, $word)) {
                        $score += 3;
                    }
                }
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestShop = $shop;
                }
            }

            $shop = $bestShop ?? $shops->first();

            return response()->json([
                'success' => true,
                'shop_id' => $shop->id,
                'shop_name' => $shop->name,
                'method' => 'fallback',
            ]);
        } catch (\Exception $e) {
            Log::error('Shop suggestion error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Detect size type from size values.
     *
     * @return string 'ring', 'clothing', or 'custom'
     */
    private function detectSizeType(array $sizes): string
    {
        if (empty($sizes)) {
            return 'custom';
        }

        $clothingLetterPattern = '/^(XXS|XS|S|M|L|XL|XXL|XXXL|2XL|3XL|4XL|5XL|6XL|ONE\s*SIZE)$/i';
        $ringPattern = '/^\d{1,2}([.,]\d{1,2})?$/';

        $allClothing = true;
        $allRing = true;

        foreach ($sizes as $size) {
            $size = trim((string) $size);

            if (! preg_match($clothingLetterPattern, $size)) {
                $allClothing = false;
            }

            if (! preg_match($ringPattern, $size)) {
                $allRing = false;
            } else {
                $num = (float) str_replace(',', '.', $size);
                // Ring sizes are typically 1-16
                if ($num < 1 || $num > 16) {
                    $allRing = false;
                }
            }
        }

        if ($allRing) {
            return 'ring';
        }

        if ($allClothing) {
            return 'clothing';
        }

        return 'custom';
    }
}
