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
     * Redirect to Etsy OAuth to add a new shop.
     */
    public function connectEtsy(): RedirectResponse
    {
        // Generate unique state for CSRF protection
        $state = Str::random(40);
        session([
            'etsy_oauth_state' => $state,
            'etsy_oauth_add_new_shop' => true,
        ]);

        $authUrl = $this->etsyClient->getAuthorizationUrl($state);

        return redirect($authUrl);
    }
}
