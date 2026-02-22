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
            'is_active' => 'boolean',
            'ai_title_prompt' => 'nullable|string|max:5000',
            'ai_description_prompt' => 'nullable|string|max:5000',
            'ai_image_prompt' => 'nullable|string|max:5000',
            'ai_image_enabled' => 'boolean',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'default_margin_us' => 'nullable|numeric|min:1|max:10',
            'default_margin_other' => 'nullable|numeric|min:1|max:10',
            'discount_percentage' => 'nullable|numeric|min:0|max:99',
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

    /**
     * Add a specific prompt for image transformation.
     */
    public function addSpecificPrompt(Request $request, Shop $shop)
    {
        Gate::authorize('update', $shop);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'prompt' => 'required|string|max:5000',
        ]);

        $prompts = $shop->ai_specific_prompts ?? [];
        $prompts[] = [
            'name' => $validated['name'],
            'prompt' => $validated['prompt'],
        ];

        $shop->update(['ai_specific_prompts' => $prompts]);

        return redirect()->route('shops.edit', $shop)
            ->with('success', 'Prompt specifique ajoute avec succes !');
    }

    /**
     * Delete a specific prompt.
     */
    public function deleteSpecificPrompt(Request $request, Shop $shop)
    {
        Gate::authorize('update', $shop);

        $validated = $request->validate([
            'index' => 'required|integer|min:0',
        ]);

        $prompts = $shop->ai_specific_prompts ?? [];
        $index = $validated['index'];

        if (isset($prompts[$index])) {
            array_splice($prompts, $index, 1);
            $shop->update(['ai_specific_prompts' => $prompts]);

            return redirect()->route('shops.edit', $shop)
                ->with('success', 'Prompt specifique supprime avec succes !');
        }

        return redirect()->route('shops.edit', $shop)
            ->with('error', 'Prompt specifique non trouve.');
    }
}
