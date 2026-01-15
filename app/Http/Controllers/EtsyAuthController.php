<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Services\EtsyApiClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class EtsyAuthController extends Controller
{
    protected EtsyApiClient $etsyClient;

    public function __construct(EtsyApiClient $etsyClient)
    {
        $this->etsyClient = $etsyClient;
    }

    /**
     * Redirect user to Etsy OAuth authorization page.
     */
    public function connect(Shop $shop)
    {
        Gate::authorize('update', $shop);

        // Generate unique state for CSRF protection
        $state = Str::random(40);
        session([
            'etsy_oauth_state' => $state,
            'etsy_oauth_shop_id' => $shop->id,
        ]);

        $authUrl = $this->etsyClient->getAuthorizationUrl($state);

        return redirect($authUrl);
    }

    /**
     * Handle Etsy OAuth callback.
     */
    public function callback(Request $request)
    {
        // Verify state to prevent CSRF
        if ($request->state !== session('etsy_oauth_state')) {
            return redirect()->route('shops.index')
                ->with('error', 'Invalid state parameter. Please try again.');
        }

        // Check for errors
        if ($request->has('error')) {
            return redirect()->route('shops.index')
                ->with('error', 'OAuth authorization failed: ' . $request->error);
        }

        // Get the shop from session
        $shopId = session('etsy_oauth_shop_id');
        $shop = Shop::findOrFail($shopId);

        Gate::authorize('update', $shop);

        try {
            // Exchange authorization code for access token
            $tokenData = $this->etsyClient->getAccessToken($request->code);

            // Create temporary shop object for API call
            $tempShop = new Shop();
            $tempShop->etsy_access_token = $tokenData['access_token'];
            $tempShop->etsy_token_expires_at = now()->addSeconds($tokenData['expires_in']);

            // Get shop ID from token data or make API call if needed
            $etsyShopId = $tokenData['shop_id'] ?? null;

            // Update shop with Etsy credentials
            $shop->update([
                'etsy_shop_id' => $etsyShopId,
                'etsy_access_token' => $tokenData['access_token'],
                'etsy_refresh_token' => $tokenData['refresh_token'],
                'etsy_token_expires_at' => now()->addSeconds($tokenData['expires_in']),
            ]);

            // Clear session data
            session()->forget(['etsy_oauth_state', 'etsy_oauth_shop_id', 'etsy_code_verifier']);

            return redirect()->route('shops.show', $shop)
                ->with('success', 'Boutique connectée à Etsy avec succès !');

        } catch (\Exception $e) {
            return redirect()->route('shops.index')
                ->with('error', 'Erreur lors de la connexion à Etsy : ' . $e->getMessage());
        }
    }

    /**
     * Disconnect shop from Etsy.
     */
    public function disconnect(Shop $shop)
    {
        Gate::authorize('update', $shop);

        $shop->update([
            'etsy_shop_id' => null,
            'etsy_access_token' => null,
            'etsy_refresh_token' => null,
            'etsy_token_expires_at' => null,
        ]);

        return redirect()->route('shops.show', $shop)
            ->with('success', 'Boutique déconnectée d\'Etsy.');
    }
}
