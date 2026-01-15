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
     * Get OAuth authorization URL.
     */
    public function getAuthorizationUrl(string $state): string
    {
        $params = http_build_query([
            'response_type' => 'code',
            'client_id' => config('etsy.client_id'),
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
        $response = Http::asForm()->post($this->oauthUrl . '/token', [
            'grant_type' => 'authorization_code',
            'client_id' => config('etsy.client_id'),
            'client_secret' => config('etsy.client_secret'),
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
     * Refresh access token using refresh token.
     */
    public function refreshAccessToken(string $refreshToken): array
    {
        $response = Http::asForm()->post($this->oauthUrl . '/token', [
            'grant_type' => 'refresh_token',
            'client_id' => config('etsy.client_id'),
            'client_secret' => config('etsy.client_secret'),
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
     * Make an authenticated API request.
     */
    protected function request(string $method, string $endpoint, array $data = []): array
    {
        if (!$this->shop || !$this->shop->etsy_access_token) {
            throw new \Exception('Shop not authenticated with Etsy');
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
            'x-api-key' => config('etsy.client_id'),
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
     */
    protected function refreshShopToken(): void
    {
        if (!$this->shop->etsy_refresh_token) {
            throw new \Exception('No refresh token available');
        }

        $tokenData = $this->refreshAccessToken($this->shop->etsy_refresh_token);

        $this->shop->update([
            'etsy_access_token' => $tokenData['access_token'],
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
