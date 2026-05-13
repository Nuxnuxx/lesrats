<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Services\PostHogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Laravel\Sanctum\PersonalAccessToken;

class ProfileController extends Controller
{
    /**
     * Nombre max de tokens API actifs par user.
     * Évite qu'un compte compromis ou un script abusif crée 1000 tokens.
     */
    private const MAX_TOKENS_PER_USER = 10;

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

        $user = $request->user();

        // Anti-bloat : cap sur le nombre de tokens actifs (hors tokens auto-extension auto-révoqués)
        if ($user->tokens()->count() >= self::MAX_TOKENS_PER_USER) {
            throw ValidationException::withMessages([
                'token_name' => 'Limite de '.self::MAX_TOKENS_PER_USER.' tokens API atteinte. Révoquez-en un avant d\'en créer un autre.',
            ]);
        }

        $token = $user->createToken($validated['token_name']);

        Log::info('API token created', [
            'user_id' => $user->id,
            'token_name' => $validated['token_name'],
        ]);

        PostHogService::capture($user->id, 'api_token_created', [
            'token_name' => $validated['token_name'],
        ]);

        return Redirect::route('profile.edit')
            ->with('new_token', $token->plainTextToken);
    }

    /**
     * Create a token for browser extension auto-connect (AJAX).
     */
    public function createExtensionToken(Request $request)
    {
        $user = $request->user();

        // Revoke any existing "Extension Auto-Connect" tokens to avoid accumulation
        $user->tokens()->where('name', 'Extension Auto-Connect')->delete();

        $token = $user->createToken('Extension Auto-Connect');

        Log::info('Extension auto-connect token created', ['user_id' => $user->id]);

        return response()->json([
            'success' => true,
            'token' => $token->plainTextToken,
            'is_admin' => $user->isAdmin(),
        ]);
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
     * Déconnexion globale : invalide TOUTES les sessions web (sauf la courante)
     * + révoque TOUS les tokens API du user. À utiliser en cas de suspicion de
     * compromission ou de perte d'un appareil.
     */
    public function logoutEverywhere(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        // 1. Tuer toutes les sessions web sauf la courante (regenerate + invalidate others)
        Auth::logoutOtherDevices($request->input('password'));

        // 2. Révoquer tous les tokens API Sanctum
        $tokenCount = $user->tokens()->count();
        $user->tokens()->delete();

        Log::warning('User logged out everywhere', [
            'user_id' => $user->id,
            'revoked_tokens' => $tokenCount,
        ]);

        return Redirect::route('profile.edit')->with('status', 'logged-out-everywhere');
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
