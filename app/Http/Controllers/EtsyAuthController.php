<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\ShopMembership;
use App\Services\EtsyApiClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
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
     * Used for reconnecting an existing shop.
     */
    public function connect(Shop $shop)
    {
        Gate::authorize('update', $shop);

        // Generate unique state for CSRF protection
        $state = Str::random(40);
        session([
            'etsy_oauth_state' => $state,
            'etsy_oauth_shop_id' => $shop->id,
            'etsy_oauth_reconnect' => true,
        ]);

        $authUrl = $this->etsyClient->getAuthorizationUrl($state);

        return redirect($authUrl);
    }

    /**
     * Handle Etsy OAuth callback.
     * This handles multiple scenarios:
     * - Onboarding: Create new shop from Etsy data
     * - Profile: Add new shop from Etsy data
     * - Reconnect: Update existing shop tokens
     */
    public function callback(Request $request)
    {
        // Verify state to prevent CSRF
        if ($request->state !== session('etsy_oauth_state')) {
            return $this->redirectWithError('Invalid state parameter. Please try again.');
        }

        // Check for OAuth errors (user cancelled, etc.)
        if ($request->has('error')) {
            return $this->redirectWithError('Connexion à Etsy annulée.');
        }

        try {
            // Exchange authorization code for access token
            $tokenData = $this->etsyClient->getAccessToken($request->code);

            // Extract Etsy user ID from token prefix (format: "12345678.xxxxx")
            $etsyUserId = explode('.', $tokenData['access_token'])[0];

            // Determine the context of this OAuth flow
            $isOnboarding = session('etsy_oauth_from_onboarding', false);
            $isAddingNewShop = session('etsy_oauth_add_new_shop', false);
            $isReconnecting = session('etsy_oauth_reconnect', false);
            $existingShopId = session('etsy_oauth_shop_id');

            // Handle reconnecting an existing shop
            if ($isReconnecting && $existingShopId) {
                return $this->handleReconnect($existingShopId, $tokenData, $etsyUserId);
            }

            // For new connections (onboarding or adding), check if Etsy account is already connected
            $existingShop = Shop::where('etsy_user_id', $etsyUserId)->first();
            if ($existingShop) {
                return $this->redirectWithError('Ce compte Etsy est déjà connecté à l\'application.');
            }

            // Fetch user's shops from Etsy API
            $etsyShopsData = $this->etsyClient->getUserShopsWithToken($tokenData['access_token'], (int) $etsyUserId);

            // Check if user has a shop on Etsy
            if (empty($etsyShopsData['results']) || count($etsyShopsData['results']) === 0) {
                return $this->redirectWithError(
                    'Aucune boutique trouvée sur ce compte Etsy. Vous devez d\'abord créer une boutique sur Etsy.',
                    'https://www.etsy.com/sell'
                );
            }

            $etsyShop = $etsyShopsData['results'][0];

            // Create the shop from Etsy data
            $shop = $this->createShopFromEtsyData($etsyShop, $tokenData, $etsyUserId);

            // Clear session data
            $this->clearOAuthSession();

            // Handle onboarding completion
            if ($isOnboarding) {
                $user = Auth::user();
                $user->update(['onboarding_completed' => true]);

                return redirect()->route('dashboard')
                    ->with('success', 'Félicitations ! Votre boutique "' . $shop->name . '" est connectée à Etsy.');
            }

            // Handle adding new shop from profile
            if ($isAddingNewShop) {
                return redirect()->route('profile.edit')
                    ->with('success', 'Boutique "' . $shop->name . '" connectée avec succès !');
            }

            // Default redirect
            return redirect()->route('shops.show', $shop)
                ->with('success', 'Boutique connectée à Etsy avec succès !');

        } catch (\Exception $e) {
            Log::error('Etsy OAuth callback error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->redirectWithError('Erreur lors de la connexion à Etsy. Veuillez réessayer.');
        }
    }

    /**
     * Handle reconnecting an existing shop (token refresh/reauthorization).
     */
    protected function handleReconnect(int $shopId, array $tokenData, string $etsyUserId)
    {
        $shop = Shop::findOrFail($shopId);

        Gate::authorize('update', $shop);

        // Update shop with new tokens
        $shop->update([
            'etsy_user_id' => $etsyUserId,
            'etsy_access_token' => $tokenData['access_token'],
            'etsy_refresh_token' => $tokenData['refresh_token'],
            'etsy_token_expires_at' => now()->addSeconds($tokenData['expires_in']),
        ]);

        $this->clearOAuthSession();

        return redirect()->route('shops.show', $shop)
            ->with('success', 'Boutique reconnectée à Etsy avec succès !');
    }

    /**
     * Create a new Shop model from Etsy shop data.
     */
    protected function createShopFromEtsyData(array $etsyShop, array $tokenData, string $etsyUserId): Shop
    {
        $shop = Shop::create([
            'name' => $etsyShop['shop_name'],
            'etsy_shop_id' => $etsyShop['shop_id'],
            'etsy_user_id' => $etsyUserId,
            'etsy_access_token' => $tokenData['access_token'],
            'etsy_refresh_token' => $tokenData['refresh_token'],
            'etsy_token_expires_at' => now()->addSeconds($tokenData['expires_in']),
            'currency' => $etsyShop['currency_code'] ?? 'EUR',
            'is_active' => true,
        ]);

        // Create membership for current user as owner
        ShopMembership::create([
            'user_id' => Auth::id(),
            'shop_id' => $shop->id,
            'role' => 'owner',
        ]);

        // Set as active shop in session
        session(['active_shop_id' => $shop->id]);

        Log::info('Shop created from Etsy data', [
            'shop_id' => $shop->id,
            'etsy_shop_id' => $etsyShop['shop_id'],
            'etsy_user_id' => $etsyUserId,
        ]);

        return $shop;
    }

    /**
     * Disconnect shop from Etsy.
     */
    public function disconnect(Shop $shop)
    {
        Gate::authorize('update', $shop);

        $shop->update([
            'etsy_shop_id' => null,
            'etsy_user_id' => null,
            'etsy_access_token' => null,
            'etsy_refresh_token' => null,
            'etsy_token_expires_at' => null,
        ]);

        return redirect()->route('shops.show', $shop)
            ->with('success', 'Boutique déconnectée d\'Etsy.');
    }

    /**
     * Clear all OAuth-related session data.
     */
    protected function clearOAuthSession(): void
    {
        session()->forget([
            'etsy_oauth_state',
            'etsy_oauth_shop_id',
            'etsy_oauth_from_onboarding',
            'etsy_oauth_add_new_shop',
            'etsy_oauth_reconnect',
            'etsy_code_verifier',
        ]);
    }

    /**
     * Redirect with error message to appropriate page.
     */
    protected function redirectWithError(string $message, ?string $link = null)
    {
        $this->clearOAuthSession();

        $isOnboarding = session('etsy_oauth_from_onboarding', false);

        if ($isOnboarding) {
            $redirect = redirect()->route('onboarding.index');
        } else {
            $redirect = redirect()->route('profile.edit');
        }

        if ($link) {
            return $redirect->with('error', $message)->with('error_link', $link);
        }

        return $redirect->with('error', $message);
    }
}
