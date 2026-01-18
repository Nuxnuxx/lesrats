<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Shop;
use App\Services\ContentOptimizerService;
use App\Services\FalImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            'headers' => $request->headers->all()
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
        ]);

        try {
            // Trouver la boutique par défaut ou la première boutique
            $shop = null;
            $user = null;

            // Si l'utilisateur est authentifié
            if (Auth::check()) {
                $user = Auth::user();
                $shop = $user->shops()->first();
            }

            // Sinon, utiliser la première boutique disponible
            if (!$shop) {
                $shop = Shop::first();
                // Essayer de récupérer un utilisateur lié à cette boutique
                if ($shop) {
                    $user = $shop->users()->first();
                }
            }

            if (!$shop) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune boutique trouvée. Créez une boutique d\'abord.'
                ], 400);
            }

            // Vérifier si le produit existe déjà (par aliexpress_product_id ou source_url)
            $existingProduct = null;
            if (!empty($validated['aliexpress_product_id'])) {
                $existingProduct = Product::where('shop_id', $shop->id)
                    ->where('aliexpress_product_id', $validated['aliexpress_product_id'])
                    ->first();
            }

            if (!$existingProduct && !empty($validated['source_url'])) {
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
                    'is_existing' => true
                ]);
            }

            // Calculer le prix de vente (marge de 2.5x par défaut)
            $costPrice = $validated['price'] ?? 0;
            $sellingPrice = $costPrice > 0 ? round($costPrice * 2.5, 2) : 0;

            // Récupérer les prompts personnalisés de la boutique
            $titlePrompt = $shop->ai_title_prompt;
            $descriptionPrompt = $shop->ai_description_prompt;
            $imagePrompt = $shop->ai_image_prompt;
            $imageEnabled = $shop->ai_image_enabled;

            // Optimiser le titre et la description avec l'IA
            $originalTitle = $validated['title'];
            $originalDescription = $validated['description'] ?? '';
            $is3DPrint = ($validated['source_type'] ?? 'aliexpress') === 'printables';
            
            try {
                $optimizer = new ContentOptimizerService();
                
                // Optimiser le titre (traduction en anglais + SEO) avec le prompt personnalisé
                $optimizedTitle = $optimizer->optimizeTitle(
                    $originalTitle, 
                    $is3DPrint ? '3D Print' : null,
                    $titlePrompt
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
            } catch (\Exception $e) {
                Log::error('Failed to optimize content', ['error' => $e->getMessage()]);
                $optimizedTitle = $originalTitle;
                $description = $originalDescription;
            }

            // Transformer les images avec Fal.ai si activé et si un prompt est configuré
            $images = $validated['images'] ?? [];
            if ($imageEnabled && !empty($imagePrompt) && !empty($images)) {
                try {
                    // Récupérer la clé API Fal.ai de l'utilisateur ou utiliser celle par défaut
                    $falApiKey = $user?->fal_api_key ?? config('services.fal.api_key');
                    
                    if ($falApiKey) {
                        $falService = new FalImageService($falApiKey);
                        $transformedImages = [];
                        
                        // Transformer chaque image (limiter à 5 pour éviter les temps de traitement trop longs)
                        foreach (array_slice($images, 0, 5) as $imageUrl) {
                            $transformedPath = $falService->transformImage($imageUrl, $imagePrompt);
                            if ($transformedPath) {
                                $transformedImages[] = $transformedPath;
                            } else {
                                // Garder l'image originale si la transformation échoue
                                $transformedImages[] = $imageUrl;
                            }
                        }
                        
                        // Ajouter les images restantes non transformées
                        if (count($images) > 5) {
                            $transformedImages = array_merge($transformedImages, array_slice($images, 5));
                        }
                        
                        $images = $transformedImages;
                        Log::info('Images transformed with Fal.ai', ['count' => count($transformedImages)]);
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to transform images', ['error' => $e->getMessage()]);
                    // Garder les images originales en cas d'erreur
                }
            }

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
                'images' => $images,
                'source_url' => $validated['source_url'],
                'source_type' => $sourceType,
                'aliexpress_product_id' => $validated['aliexpress_product_id'] ?? null,
                'aliexpress_url' => $validated['source_url'],
                'is_active' => true,
                'quantity' => $quantity,
                'is_digital' => $isDigital,
                'low_stock_threshold' => $lowStockThreshold,
                'etsy_sync_status' => Product::SYNC_STATUS_NOT_SYNCED,
            ]);

            Log::info('Product imported via extension', [
                'product_id' => $product->id,
                'title' => $product->title,
                'images_transformed' => $imageEnabled && !empty($imagePrompt)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Produit importé avec succès!',
                'product_id' => $product->id,
                'product_url' => route('products.edit', $product),
                'is_existing' => false
            ]);
        } catch (\Exception $e) {
            Log::error('Extension import error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'import: ' . $e->getMessage()
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
            'version' => '1.0.0'
        ]);
    }
}
