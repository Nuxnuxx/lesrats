<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Laravel\Sanctum\PersonalAccessToken;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
            'shops' => $request->user()->shops,
            'tokens' => $request->user()->tokens()->orderBy('created_at', 'desc')->get(),
        ]);
    }

    /**
     * Create a new API token for the extension.
     */
    public function createToken(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token_name' => 'required|string|max:255',
        ]);

        $token = $request->user()->createToken($validated['token_name']);

        return Redirect::route('profile.edit')
            ->with('new_token', $token->plainTextToken);
    }

    /**
     * Revoke an API token.
     */
    public function revokeToken(Request $request, PersonalAccessToken $token): RedirectResponse
    {
        // Ensure the token belongs to the current user
        if ($token->tokenable_id !== $request->user()->id) {
            abort(403);
        }

        $token->delete();

        return Redirect::route('profile.edit')->with('status', 'token-revoked');
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
        if (! empty($validated['fal_api_key'])) {
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
}
