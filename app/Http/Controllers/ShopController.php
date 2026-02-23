<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class ShopController extends Controller
{
    /**
     * Display a listing of the resource.
     * Redirects to dashboard (kept for route compatibility).
     */
    public function index()
    {
        return redirect()->route('dashboard');
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
            'product_type' => 'required|in:physical,virtual',
            'default_price' => 'nullable|numeric|min:0',
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

        return redirect()->route('shops.edit', $shop)
            ->with('success', 'Boutique creee avec succes !');
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
            'product_type' => 'nullable|in:physical,virtual',
            'default_price' => 'nullable|numeric|min:0',
            'ai_title_prompt' => 'nullable|string|max:5000',
            'ai_description_prompt' => 'nullable|string|max:5000',
            'ai_image_prompt' => 'nullable|string|max:5000',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'shipping_fee' => 'nullable|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:99',
            'expert_mode' => 'boolean',
            'pricing_k' => 'nullable|numeric|min:0.01|max:100',
            'pricing_t' => 'nullable|numeric|min:0|max:0.99',
            'pricing_t0' => 'nullable|numeric|min:0',
        ]);

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
     * Auto-save shop fields (partial update via AJAX).
     */
    public function autosave(Request $request, Shop $shop)
    {
        Gate::authorize('update', $shop);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string|max:5000',
            'currency' => 'sometimes|string|size:3',
            'product_type' => 'sometimes|in:physical,virtual',
            'default_price' => 'sometimes|nullable|numeric|min:0',
            'shipping_fee' => 'sometimes|nullable|numeric|min:0',
            'discount_percentage' => 'sometimes|nullable|numeric|min:0|max:99',
            'expert_mode' => 'sometimes|boolean',
            'pricing_k' => 'sometimes|nullable|numeric|min:0.01|max:100',
            'pricing_t' => 'sometimes|nullable|numeric|min:0|max:0.99',
            'pricing_t0' => 'sometimes|nullable|numeric|min:0',
            'promotion_reminder_date' => 'sometimes|nullable|date',
            'ai_description_prompt' => 'sometimes|nullable|string|max:5000',
            'ai_image_prompt' => 'sometimes|nullable|string|max:5000',
            'ai_specific_prompts' => 'sometimes|nullable|string',
            'etsy_categories' => 'sometimes|nullable|string',
            'available_tags' => 'sometimes|nullable|string',
        ]);

        // Decode JSON strings for array fields
        if (isset($validated['ai_specific_prompts']) && is_string($validated['ai_specific_prompts'])) {
            $prompts = json_decode($validated['ai_specific_prompts'], true) ?? [];
            $validated['ai_specific_prompts'] = collect($prompts)
                ->filter(fn ($p) => ! empty($p['name']) || ! empty($p['prompt']))
                ->map(fn ($p) => [
                    'name' => $p['name'] ?? '',
                    'prompt' => $p['prompt'] ?? '',
                ])
                ->values()
                ->toArray();
        }

        if (isset($validated['etsy_categories']) && is_string($validated['etsy_categories'])) {
            $categories = json_decode($validated['etsy_categories'], true) ?? [];
            $validated['etsy_categories'] = collect($categories)
                ->filter(fn ($cat) => ! empty($cat['name']) || ! empty($cat['etsy_name']))
                ->map(fn ($cat) => [
                    'name' => $cat['name'] ?? '',
                    'etsy_name' => $cat['etsy_name'] ?? '',
                    'etsy_id' => $cat['etsy_id'] ?? '',
                    'keywords' => $cat['keywords'] ?? '',
                ])
                ->values()
                ->toArray();
        }

        if (isset($validated['available_tags']) && is_string($validated['available_tags'])) {
            $tags = json_decode($validated['available_tags'], true) ?? [];
            $validated['available_tags'] = collect($tags)
                ->map(fn ($tag) => strtolower(trim($tag)))
                ->filter(fn ($tag) => ! empty($tag))
                ->unique()
                ->values()
                ->toArray();
        }

        $shop->update($validated);

        return response()->json(['success' => true]);
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

    /**
     * Upload a background image for AI generation.
     */
    public function uploadBackground(Request $request, Shop $shop)
    {
        Gate::authorize('update', $shop);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'background' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120', // Max 5MB
        ]);

        // Store the background image
        $path = $request->file('background')->store("backgrounds/shop_{$shop->id}", 'public');

        // Get existing backgrounds or initialize empty array
        $backgrounds = $shop->ai_backgrounds ?? [];

        // Add new background
        $backgrounds[] = [
            'name' => $validated['name'],
            'path' => $path,
        ];

        $shop->update(['ai_backgrounds' => $backgrounds]);

        return redirect()->route('shops.edit', $shop)
            ->with('success', 'Background "'.$validated['name'].'" ajoute avec succes !');
    }

    /**
     * Delete a background image.
     */
    public function deleteBackground(Request $request, Shop $shop)
    {
        Gate::authorize('update', $shop);

        $validated = $request->validate([
            'index' => 'required|integer|min:0',
        ]);

        $backgrounds = $shop->ai_backgrounds ?? [];
        $index = $validated['index'];

        if (isset($backgrounds[$index])) {
            // Delete the file from storage
            $path = $backgrounds[$index]['path'] ?? null;
            if ($path && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }

            // Clear default if this was the last used background
            if ($shop->default_ai_background === $path) {
                $shop->default_ai_background = null;
            }

            // Remove from array
            array_splice($backgrounds, $index, 1);

            $shop->update([
                'ai_backgrounds' => $backgrounds,
                'default_ai_background' => $shop->default_ai_background,
            ]);

            return redirect()->route('shops.edit', $shop)
                ->with('success', 'Background supprime avec succes !');
        }

        return redirect()->route('shops.edit', $shop)
            ->with('error', 'Background non trouve.');
    }
}
