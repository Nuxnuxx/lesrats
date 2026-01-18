<?php

namespace App\Services;

use App\Models\Shop;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class EtsyApiClient
{
    protected string $apiUrl;
    protected string $oauthUrl;
    protected ?Shop $shop;

    public function __construct(?Shop $shop = null)
    {
        $this->apiUrl = config('etsy.api_url');
        $this->oauthUrl = config('etsy.oauth_url');
        $this->shop = $shop;
    }

    /**
     * Set the shop for this API client instance.
     */
    public function setShop(Shop $shop): self
    {
        $this->shop = $shop;
        return $this;
    }

    /**
     * Check if mock mode is enabled.
     */
    public function isMockMode(): bool
    {
        return config('etsy.mock_enabled', false);
    }

    /**
     * Get the client ID for API requests.
     * Uses shop credentials if available, otherwise falls back to config.
     */
    protected function getClientId(): ?string
    {
        if ($this->shop && $this->shop->etsy_client_id) {
            return $this->shop->etsy_client_id;
        }
        return config('etsy.client_id');
    }

    /**
     * Get the client secret for OAuth.
     * Uses shop credentials if available, otherwise falls back to config.
     */
    protected function getClientSecret(): ?string
    {
        if ($this->shop && $this->shop->etsy_client_secret) {
            return $this->shop->etsy_client_secret;
        }
        return config('etsy.client_secret');
    }

    /**
     * Get OAuth authorization URL.
     */
    public function getAuthorizationUrl(string $state): string
    {
        $clientId = $this->getClientId();
        
        if (!$clientId) {
            throw new \Exception('Etsy client_id not configured. Please add your Etsy API credentials in shop settings.');
        }

        // In mock mode, redirect to local mock authorization page
        if ($this->isMockMode()) {
            $params = http_build_query([
                'client_id' => $clientId,
                'redirect_uri' => url('/etsy/callback'),
                'scope' => implode(' ', config('etsy.scopes')),
                'state' => $state,
            ]);

            return url('/etsy/mock/authorize') . '?' . $params;
        }

        $params = http_build_query([
            'response_type' => 'code',
            'client_id' => $clientId,
            'redirect_uri' => config('etsy.redirect_uri'),
            'scope' => implode(' ', config('etsy.scopes')),
            'state' => $state,
            'code_challenge' => $this->generateCodeChallenge(),
            'code_challenge_method' => 'S256',
        ]);

        return $this->oauthUrl . '/connect?' . $params;
    }

    /**
     * Exchange authorization code for access token.
     */
    public function getAccessToken(string $code): array
    {
        // In mock mode, return fake token data from session
        if ($this->isMockMode()) {
            return $this->getMockAccessToken($code);
        }

        $clientId = $this->getClientId();
        $clientSecret = $this->getClientSecret();

        if (!$clientId || !$clientSecret) {
            throw new \Exception('Etsy API credentials not configured. Please add your Etsy client_id and client_secret in shop settings.');
        }

        $response = Http::asForm()->post($this->oauthUrl . '/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => config('etsy.redirect_uri'),
            'code' => $code,
            'code_verifier' => session('etsy_code_verifier'),
        ]);

        if ($response->failed()) {
            Log::error('Etsy OAuth token exchange failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Failed to exchange authorization code for access token');
        }

        return $response->json();
    }

    /**
     * Get mock access token data (for development).
     */
    protected function getMockAccessToken(string $code): array
    {
        // Verify the mock code matches what we stored
        if ($code !== session('etsy_mock_code')) {
            throw new \Exception('Invalid mock authorization code');
        }

        $userId = session('etsy_mock_user_id', rand(10000000, 99999999));

        return [
            'access_token' => $userId . '.mock_access_token_' . bin2hex(random_bytes(16)),
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'refresh_token' => $userId . '.mock_refresh_token_' . bin2hex(random_bytes(16)),
        ];
    }

    /**
     * Refresh access token using refresh token.
     */
    public function refreshAccessToken(string $refreshToken): array
    {
        // In mock mode, return fake refreshed tokens
        if ($this->isMockMode()) {
            return $this->getMockRefreshedToken($refreshToken);
        }

        $clientId = $this->getClientId();
        $clientSecret = $this->getClientSecret();

        if (!$clientId || !$clientSecret) {
            throw new \Exception('Etsy API credentials not configured for token refresh.');
        }

        $response = Http::asForm()->post($this->oauthUrl . '/token', [
            'grant_type' => 'refresh_token',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'refresh_token' => $refreshToken,
        ]);

        if ($response->failed()) {
            Log::error('Etsy token refresh failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Failed to refresh access token');
        }

        return $response->json();
    }

    /**
     * Get mock refreshed token data (for development).
     */
    protected function getMockRefreshedToken(string $refreshToken): array
    {
        // Extract user_id from the refresh token prefix
        $userId = explode('.', $refreshToken)[0];

        return [
            'access_token' => $userId . '.mock_access_token_' . bin2hex(random_bytes(16)),
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'refresh_token' => $userId . '.mock_refresh_token_' . bin2hex(random_bytes(16)),
        ];
    }

    /**
     * Make an authenticated API request.
     */
    protected function request(string $method, string $endpoint, array $data = []): array
    {
        if (!$this->shop || !$this->shop->etsy_access_token) {
            throw new \Exception('Shop not authenticated with Etsy');
        }

        $clientId = $this->getClientId();
        if (!$clientId) {
            throw new \Exception('Etsy client_id not configured for this shop.');
        }

        // Check if token is expired and refresh if needed
        if ($this->shop->etsy_token_expires_at && $this->shop->etsy_token_expires_at->isPast()) {
            $this->refreshShopToken();
        }

        // Rate limiting
        $this->enforceRateLimit();

        $url = $this->apiUrl . '/' . ltrim($endpoint, '/');

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->shop->etsy_access_token,
            'x-api-key' => $clientId,
        ])->$method($url, $data);

        if ($response->failed()) {
            Log::error('Etsy API request failed', [
                'method' => $method,
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \Exception('Etsy API request failed: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * GET request to Etsy API.
     */
    public function get(string $endpoint): array
    {
        return $this->request('get', $endpoint);
    }

    /**
     * POST request to Etsy API.
     */
    public function post(string $endpoint, array $data = []): array
    {
        return $this->request('post', $endpoint, $data);
    }

    /**
     * PUT request to Etsy API.
     */
    public function put(string $endpoint, array $data = []): array
    {
        return $this->request('put', $endpoint, $data);
    }

    /**
     * PATCH request to Etsy API.
     */
    public function patch(string $endpoint, array $data = []): array
    {
        return $this->request('patch', $endpoint, $data);
    }

    /**
     * DELETE request to Etsy API.
     */
    public function delete(string $endpoint): array
    {
        return $this->request('delete', $endpoint);
    }

    /**
     * Refresh the shop's access token.
     * Note: Etsy returns a NEW refresh token with each refresh, so we save both.
     */
    protected function refreshShopToken(): void
    {
        if (!$this->shop->etsy_refresh_token) {
            throw new \Exception('No refresh token available');
        }

        $tokenData = $this->refreshAccessToken($this->shop->etsy_refresh_token);

        // Update both access token AND refresh token (Etsy gives new refresh token each time)
        $this->shop->update([
            'etsy_access_token' => $tokenData['access_token'],
            'etsy_refresh_token' => $tokenData['refresh_token'],
            'etsy_token_expires_at' => now()->addSeconds($tokenData['expires_in']),
        ]);

        Log::info('Etsy token refreshed for shop', ['shop_id' => $this->shop->id]);
    }

    /**
     * Enforce rate limiting (10 requests per second).
     */
    protected function enforceRateLimit(): void
    {
        $key = 'etsy_rate_limit_' . ($this->shop ? $this->shop->id : 'global');
        $maxRequests = config('etsy.rate_limit.max_requests');
        $perSeconds = config('etsy.rate_limit.per_seconds');

        $requests = Cache::get($key, 0);

        if ($requests >= $maxRequests) {
            sleep($perSeconds);
            Cache::put($key, 0, $perSeconds);
        } else {
            Cache::put($key, $requests + 1, $perSeconds);
        }
    }

    /**
     * Generate PKCE code challenge.
     */
    protected function generateCodeChallenge(): string
    {
        $verifier = bin2hex(random_bytes(32));
        session(['etsy_code_verifier' => $verifier]);

        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

        return $challenge;
    }

    // ===========================================
    // ETSY API METHODS
    // ===========================================

    /**
     * Get shops for an Etsy user using a provided access token.
     * Used during OAuth callback before a Shop model is created.
     *
     * @param string $accessToken The OAuth access token
     * @param int $userId The Etsy user ID (extracted from token prefix)
     * @return array The API response containing shop data
     */
    public function getUserShopsWithToken(string $accessToken, int $userId): array
    {
        // In mock mode, return fake shop data from session
        if ($this->isMockMode()) {
            return $this->getMockUserShops();
        }

        $clientId = $this->getClientId();
        if (!$clientId) {
            throw new \Exception('Etsy client_id not configured.');
        }

        $url = $this->apiUrl . "/application/users/{$userId}/shops";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'x-api-key' => $clientId,
        ])->get($url);

        if ($response->failed()) {
            Log::error('Etsy get user shops failed', [
                'user_id' => $userId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Failed to fetch Etsy shops: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Get mock user shops data (for development).
     */
    protected function getMockUserShops(): array
    {
        $shopName = session('etsy_mock_shop_name', 'Ma Boutique Test');
        $shopId = session('etsy_mock_shop_id', rand(10000000, 99999999));
        $userId = session('etsy_mock_user_id', rand(10000000, 99999999));
        $currency = session('etsy_mock_currency', 'EUR');

        return [
            'count' => 1,
            'results' => [
                [
                    'shop_id' => (int) $shopId,
                    'shop_name' => $shopName,
                    'user_id' => (int) $userId,
                    'title' => $shopName,
                    'currency_code' => $currency,
                    'url' => 'https://www.etsy.com/shop/' . str_replace(' ', '', $shopName),
                    'image_url_760x100' => null,
                    'num_favorers' => rand(10, 1000),
                    'listing_active_count' => rand(0, 50),
                    'sale_count' => rand(0, 500),
                    'review_average' => round(rand(40, 50) / 10, 1),
                    'review_count' => rand(0, 100),
                ],
            ],
        ];
    }

    /**
     * Get shop information.
     */
    public function getShop(int $shopId): array
    {
        return $this->get("/application/shops/{$shopId}");
    }

    /**
     * Get shop listings.
     */
    public function getListings(int $shopId, array $params = []): array
    {
        $query = http_build_query($params);
        return $this->get("/application/shops/{$shopId}/listings/active?{$query}");
    }

    /**
     * Get a single listing.
     */
    public function getListing(int $listingId): array
    {
        return $this->get("/application/listings/{$listingId}");
    }

    /**
     * Create a new listing.
     */
    public function createListing(int $shopId, array $data): array
    {
        return $this->post("/application/shops/{$shopId}/listings", $data);
    }

    /**
     * Update a listing.
     */
    public function updateListing(int $shopId, int $listingId, array $data): array
    {
        return $this->patch("/application/shops/{$shopId}/listings/{$listingId}", $data);
    }

    /**
     * Delete a listing.
     */
    public function deleteListing(int $listingId): array
    {
        return $this->delete("/application/listings/{$listingId}");
    }

    /**
     * Get shop transactions (orders).
     */
    public function getTransactions(int $shopId, array $params = []): array
    {
        $query = http_build_query($params);
        return $this->get("/application/shops/{$shopId}/transactions?{$query}");
    }

    /**
     * Upload listing image.
     */
    public function uploadListingImage(int $shopId, int $listingId, string $imagePath): array
    {
        // This requires multipart/form-data which needs special handling
        // Will be implemented when needed
        throw new \Exception('Image upload not yet implemented');
    }
}
