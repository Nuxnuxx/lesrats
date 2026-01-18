<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Services\EtsyApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProfileController extends Controller
{
    protected EtsyApiClient $etsyClient;

    public function __construct(EtsyApiClient $etsyClient)
    {
        $this->etsyClient = $etsyClient;
    }

    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
            'shops' => $request->user()->shops,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Update the user's API keys.
     */
    public function updateApiKeys(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'fal_api_key' => 'nullable|string|max:500',
        ]);

        $user = $request->user();
        
        // Only update if a non-empty value is provided
        // Empty string = keep existing, new value = replace
        if (!empty($validated['fal_api_key'])) {
            $user->fal_api_key = $validated['fal_api_key'];
            $user->save();
        }

        return Redirect::route('profile.edit')->with('status', 'api-keys-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    /**
     * Handle Etsy credentials submission and redirect to OAuth to add a new shop.
     */
    public function connectEtsy(Request $request): RedirectResponse
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
            'etsy_oauth_add_new_shop' => true,
            'etsy_pending_client_id' => $validated['etsy_client_id'],
            'etsy_pending_client_secret' => $validated['etsy_client_secret'],
        ]);

        // Create a temporary Shop model (not persisted) to use its credentials
        $tempShop = new \App\Models\Shop([
            'etsy_client_id' => $validated['etsy_client_id'],
            'etsy_client_secret' => $validated['etsy_client_secret'],
        ]);

        // Set the temp shop on the API client so it uses these credentials
        $this->etsyClient->setShop($tempShop);
        
        try {
            $authUrl = $this->etsyClient->getAuthorizationUrl($state);
            return redirect($authUrl);
        } catch (\Exception $e) {
            return redirect()->route('profile.edit')
                ->with('error', 'Erreur lors de la connexion : ' . $e->getMessage());
        }
    }
}
