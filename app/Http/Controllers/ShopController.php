<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ShopController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $shops = auth()->user()->shops()->with('members')->get();

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
            'currency' => 'required|string|size:3',
        ]);

        $shop = Shop::create($validated);

        // Attach current user as owner
        $shop->users()->attach(auth()->id(), ['role' => 'owner']);

        return redirect()->route('shops.index')
            ->with('success', 'Boutique créée avec succès !');
    }

    /**
     * Display the specified resource.
     */
    public function show(Shop $shop)
    {
        Gate::authorize('view', $shop);

        $shop->load('members.user');

        return view('shops.show', compact('shop'));
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
            'currency' => 'required|string|size:3',
            'is_active' => 'boolean',
        ]);

        $shop->update($validated);

        return redirect()->route('shops.index')
            ->with('success', 'Boutique mise à jour avec succès !');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Shop $shop)
    {
        Gate::authorize('delete', $shop);

        $shop->delete();

        return redirect()->route('shops.index')
            ->with('success', 'Boutique supprimée avec succès !');
    }

    /**
     * Switch active shop.
     */
    public function switch(Shop $shop)
    {
        Gate::authorize('view', $shop);

        session(['active_shop_id' => $shop->id]);

        return redirect()->back()
            ->with('success', "Boutique active changée vers : {$shop->name}");
    }
}
