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
            // Trouver la boutique
            $shop = null;

            // Si un shop_id est fourni, l'utiliser
            if (! empty($validated['shop_id'])) {
                $shop = Shop::find($validated['shop_id']);
            }

            // Sinon, utiliser la première boutique disponible
            if (! $shop) {
                $shop = Shop::first();
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
                return response()->json([
                    'success' => true,
                    'message' => 'Produit déjà importé',
                    'product_id' => $existingProduct->id,
                    'product_url' => route('products.edit', $existingProduct),
                    'is_existing' => true,
                ]);
            }

            // Calculer le prix de vente (marge de 2.5x par défaut)
            $costPrice = $validated['price'] ?? 0;
            $sellingPrice = $costPrice > 0 ? round($costPrice * 2.5, 2) : 0;

            // Process country-specific pricing if available
            $countryPrices = $validated['country_prices'] ?? null;
            $priceUs = null;
            $priceOther = null;

            if ($countryPrices && ! empty($countryPrices)) {
                // Get shop's default margins
                $marginUs = (float) ($shop->default_margin_us ?? 2.5);
                $marginOther = (float) ($shop->default_margin_other ?? 2.5);

                // Calculate US price
                if (isset($countryPrices['US']['total'])) {
                    $priceUs = round((float) $countryPrices['US']['total'] * $marginUs, 2);
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
                    $priceOther = round($maxOtherTotal * $marginOther, 2);
                }

                Log::info('Country prices processed', [
                    'country_prices' => $countryPrices,
                    'margin_us' => $marginUs,
                    'margin_other' => $marginOther,
                    'price_us' => $priceUs,
                    'price_other' => $priceOther,
                ]);
            }

            // Récupérer les prompts personnalisés de la boutique (avec niche injectée)
            $descriptionPrompt = $shop->getEffectiveAiDescriptionPrompt();

            // Optimiser le titre et la description avec l'IA
            $originalTitle = $validated['title'];
            $originalDescription = $validated['description'] ?? '';
            $is3DPrint = ($validated['source_type'] ?? 'aliexpress') === 'printables';

            try {
                $optimizer = new ContentOptimizerService;

                // Optimiser le titre (traduction en anglais + SEO) avec le prompt personnalisé
                $optimizedTitle = $optimizer->optimizeTitle(
                    $originalTitle,
                    $is3DPrint ? '3D Print' : null,
                    $descriptionPrompt
                );
                Log::info('Optimized title', ['original' => $originalTitle, 'optimized' => $optimizedTitle]);

                // Générer une description optimisée avec le prompt personnalisé
                $description = $optimizer->optimizeDescription(
                    $originalTitle,
                    $originalDescription,
                    $validated['specifications'] ?? [],
                    $is3DPrint,
                    $descriptionPrompt
                );
                Log::info('Generated description from title', ['title' => $originalTitle]);

                // Générer les tags (utilise la liste de tags de la boutique si disponible)
                $tags = $optimizer->selectRelevantTags(
                    $optimizedTitle,
                    $description,
                    $shop->available_tags ?? [],
                    $is3DPrint
                );
                Log::info('Generated tags', ['count' => count($tags), 'tags' => $tags]);

                // Sélectionner la catégorie Etsy (si des catégories sont définies)
                $etsyCategory = $optimizer->selectCategory(
                    $optimizedTitle,
                    $description,
                    $shop->etsy_categories ?? []
                );
                Log::info('Selected category', ['category' => $etsyCategory]);
            } catch (\Exception $e) {
                Log::error('Failed to optimize content', ['error' => $e->getMessage()]);
                $optimizedTitle = $originalTitle;
                $description = $originalDescription;
                $tags = [];
                $etsyCategory = null;
            }

            // Images are kept as-is during import
            // AI image generation can be triggered manually from the product edit page
            $images = $validated['images'] ?? [];

            // Déterminer le stock et les paramètres selon le type de source
            $sourceType = $validated['source_type'] ?? 'aliexpress';
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
                'is_active' => true,
                'quantity' => $quantity,
                'is_digital' => $isDigital,
                'low_stock_threshold' => $lowStockThreshold,
            ]);

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
     */
    public function getShops()
    {
        try {
            $shops = Shop::select('id', 'name')->get();

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
    public function getEtsyData($id)
    {
        try {
            $product = Product::with('shop')->findOrFail($id);

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

            // Get category info from shop's etsy_categories
            $categoryData = null;
            if ($product->etsy_category && $product->shop->etsy_categories) {
                foreach ($product->shop->etsy_categories as $cat) {
                    if ($cat['name'] === $product->etsy_category) {
                        $categoryData = $cat;
                        break;
                    }
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $product->id,
                    'title' => $product->title,
                    'description' => $product->description,
                    'price' => (float) $product->price,
                    'price_us' => $product->price_us ? (float) $product->price_us : null,
                    'price_other' => $product->price_other ? (float) $product->price_other : null,
                    'tags' => $tags,
                    'images' => $imagesToUse,
                    'quantity' => $product->quantity ?? 999,
                    'is_digital' => (bool) $product->is_digital,
                    'shop_name' => $product->shop->name,
                    'shop_id' => $product->shop->id,
                    'etsy_category' => $categoryData,
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
}
