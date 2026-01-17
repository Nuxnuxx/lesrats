<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Mock controller for testing Etsy OAuth flow without a real API key.
 * Only available when ETSY_MOCK_ENABLED=true
 */
class EtsyMockController extends Controller
{
    /**
     * Show the mock OAuth authorization page.
     * Simulates Etsy's authorization screen.
     */
    public function authorize(Request $request)
    {
        if (!config('etsy.mock_enabled')) {
            abort(404);
        }

        return view('etsy-mock.authorize', [
            'state' => $request->state,
            'redirect_uri' => $request->redirect_uri,
            'client_id' => $request->client_id,
            'scope' => $request->scope,
        ]);
    }

    /**
     * Handle the mock authorization approval.
     * Redirects back to the app with a fake authorization code.
     */
    public function approve(Request $request)
    {
        if (!config('etsy.mock_enabled')) {
            abort(404);
        }

        $redirectUri = $request->redirect_uri;
        $state = $request->state;
        $code = 'mock_auth_code_' . Str::random(32);

        // Store mock data in session for the token exchange
        session([
            'etsy_mock_code' => $code,
            'etsy_mock_shop_name' => $request->shop_name ?? 'Ma Boutique Test',
            'etsy_mock_shop_id' => $request->shop_id ?? rand(10000000, 99999999),
            'etsy_mock_user_id' => $request->user_id ?? rand(10000000, 99999999),
            'etsy_mock_currency' => $request->currency ?? 'EUR',
        ]);

        return redirect($redirectUri . '?' . http_build_query([
            'code' => $code,
            'state' => $state,
        ]));
    }

    /**
     * Handle the mock authorization denial.
     */
    public function deny(Request $request)
    {
        if (!config('etsy.mock_enabled')) {
            abort(404);
        }

        $redirectUri = $request->redirect_uri;
        $state = $request->state;

        return redirect($redirectUri . '?' . http_build_query([
            'error' => 'access_denied',
            'error_description' => 'The user denied the authorization request.',
            'state' => $state,
        ]));
    }
}
