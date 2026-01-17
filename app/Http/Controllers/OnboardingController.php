<?php

namespace App\Http\Controllers;

use App\Services\EtsyApiClient;
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
     * Redirect to Etsy OAuth for onboarding.
     * No shop parameter needed - shop will be created from Etsy data.
     */
    public function connectEtsy()
    {
        // Generate unique state for CSRF protection
        $state = Str::random(40);
        session([
            'etsy_oauth_state' => $state,
            'etsy_oauth_from_onboarding' => true,
        ]);

        $authUrl = $this->etsyClient->getAuthorizationUrl($state);

        return redirect($authUrl);
    }
}
