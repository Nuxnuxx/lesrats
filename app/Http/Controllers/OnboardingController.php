<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\ShopMembership;
use App\Services\EtsyApiClient;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OnboardingController extends Controller
{
    protected EtsyApiClient $etsyClient;

    public function __construct(EtsyApiClient $etsyClient)
    {
        $this->etsyClient = $etsyClient;
    }

    /**
     * Show the onboarding welcome/shop creation step.
     */
    public function index()
    {
        $user = auth()->user();

        // If onboarding is already completed, redirect to dashboard
        if ($user->onboarding_completed) {
            return redirect()->route('dashboard');
        }

        // Check if user already has a shop (created during onboarding)
        $shop = $user->shops()->first();

        return view('onboarding.index', [
            'shop' => $shop,
            'step' => $shop ? 2 : 1,
        ]);
    }

    /**
     * Store the shop created during onboarding.
     */
    public function storeShop(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'currency' => ['required', 'string', 'in:EUR,USD,GBP,CAD'],
        ]);

        $user = auth()->user();

        // Create the shop
        $shop = Shop::create([
            'name' => $request->name,
            'currency' => $request->currency,
            'is_active' => true,
        ]);

        // Create membership as owner
        ShopMembership::create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'role' => 'owner',
        ]);

        // Set as active shop
        session(['active_shop_id' => $shop->id]);

        return redirect()->route('onboarding.index')
            ->with('success', 'Boutique créée avec succès ! Vous pouvez maintenant la connecter à Etsy.');
    }

    /**
     * Redirect to Etsy OAuth for onboarding.
     */
    public function connectEtsy(Shop $shop)
    {
        $user = auth()->user();

        if (!$user->hasAccessToShop($shop)) {
            abort(403);
        }

        // Generate unique state for CSRF protection
        $state = Str::random(40);
        session([
            'etsy_oauth_state' => $state,
            'etsy_oauth_shop_id' => $shop->id,
            'etsy_oauth_from_onboarding' => true,
        ]);

        $authUrl = $this->etsyClient->getAuthorizationUrl($state);

        return redirect($authUrl);
    }

    /**
     * Skip Etsy connection and complete onboarding.
     */
    public function skip()
    {
        $user = auth()->user();

        // Mark onboarding as completed
        $user->update(['onboarding_completed' => true]);

        return redirect()->route('dashboard')
            ->with('success', 'Bienvenue ! Vous pouvez connecter Etsy plus tard depuis votre profil.');
    }

    /**
     * Complete onboarding after Etsy connection.
     */
    public function complete()
    {
        $user = auth()->user();

        // Mark onboarding as completed
        $user->update(['onboarding_completed' => true]);

        return redirect()->route('dashboard')
            ->with('success', 'Félicitations ! Votre boutique est connectée à Etsy.');
    }
}
