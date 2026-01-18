<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Shop;
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

            // Si l'utilisateur est authentifié
            if (Auth::check()) {
                $shop = Auth::user()->shops()->first();
            }

            // Sinon, utiliser la première boutique disponible
            if (!$shop) {
                $shop = Shop::first();
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

            // Créer le produit
            $product = Product::create([
                'shop_id' => $shop->id,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? '',
                'price' => $sellingPrice,
                'cost_price' => $costPrice,
                'images' => $validated['images'] ?? [],
                'source_url' => $validated['source_url'],
                'source_type' => $validated['source_type'] ?? 'aliexpress',
                'aliexpress_product_id' => $validated['aliexpress_product_id'] ?? null,
                'aliexpress_url' => $validated['source_url'],
                'is_active' => true,
                'quantity' => 999, // Stock illimité par défaut (dropshipping)
                'is_digital' => false,
                'low_stock_threshold' => 5,
                'etsy_sync_status' => Product::SYNC_STATUS_NOT_SYNCED,
            ]);

            Log::info('Product imported via extension', [
                'product_id' => $product->id,
                'title' => $product->title
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
