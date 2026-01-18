<?php

namespace App\Http\Controllers;

use App\Models\Shop;
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
     * Show the onboarding page.
     * Single step: Connect Etsy account.
     */
    public function index()
    {
        $user = auth()->user();

        // If onboarding is already completed, redirect to dashboard
        if ($user->onboarding_completed) {
            return redirect()->route('dashboard');
        }

        return view('onboarding.index');
    }

    /**
     * Handle Etsy credentials submission and redirect to OAuth.
     * Stores credentials in session, then redirects to Etsy OAuth.
     */
    public function connectEtsy(Request $request)
    {
        $validated = $request->validate([
            'etsy_client_id' => 'required|string|max:255',
            'etsy_client_secret' => 'required|string|max:255',
        ]);

        // Generate unique state for CSRF protection
        $state = Str::random(40);
        
        // Store credentials and OAuth state in session
        session([
            'etsy_oauth_state' => $state,
            'etsy_oauth_from_onboarding' => true,
            'etsy_pending_client_id' => $validated['etsy_client_id'],
            'etsy_pending_client_secret' => $validated['etsy_client_secret'],
        ]);

        // Create a temporary Shop model (not persisted) to use its credentials
        $tempShop = new Shop([
            'etsy_client_id' => $validated['etsy_client_id'],
            'etsy_client_secret' => $validated['etsy_client_secret'],
        ]);

        // Set the temp shop on the API client so it uses these credentials
        $this->etsyClient->setShop($tempShop);
        
        try {
            $authUrl = $this->etsyClient->getAuthorizationUrl($state);
            return redirect($authUrl);
        } catch (\Exception $e) {
            return redirect()->route('onboarding.index')
                ->with('error', 'Erreur lors de la connexion : ' . $e->getMessage());
        }
    }
}
