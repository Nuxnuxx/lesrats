<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FalImageService
{
    protected ?string $apiKey;

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?? config('services.fal.api_key');
    }

    /**
     * Transform an image using Nano Banana (Google Gemini 2.5 Flash Image) via Fal.ai
     * Cost: ~$0.039 per image (~256 images for $10)
     *
     * @param  string  $imageUrl  The source image URL
     * @param  string  $prompt  The transformation prompt
     * @param  float  $strength  Unused (kept for API compatibility)
     * @param  string|null  $backgroundUrl  Optional reference image URL
     * @return string|null The local path to the transformed image, or null on failure
     */
    public function transformImage(string $imageUrl, string $prompt, float $strength = 0.65, ?string $backgroundUrl = null): ?string
    {
        if (! $this->apiKey) {
            Log::warning('Fal.ai API key not configured');

            return null;
        }

        try {
            // Build image URLs array (Nano Banana supports multiple reference images)
            $imageUrls = [$imageUrl];
            if ($backgroundUrl) {
                $imageUrls[] = $backgroundUrl;
                Log::info('Using background reference', ['background_url' => $backgroundUrl]);
            }

            // Build the request payload for Nano Banana
            $payload = [
                'image_urls' => $imageUrls,
                'prompt' => $prompt,
                'num_images' => 1,
                'output_format' => 'jpeg',
                'aspect_ratio' => 'auto',
            ];

            // Submit request using synchronous endpoint (fal.run waits for result)
            $response = Http::withHeaders([
                'Authorization' => 'Key '.$this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(120)->post('https://fal.run/fal-ai/nano-banana/edit', $payload);

            if (! $response->successful()) {
                Log::error('Fal.ai API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $result = $response->json();

            // Get the generated image URL
            $generatedImageUrl = $result['images'][0]['url'] ?? null;

            if (! $generatedImageUrl) {
                Log::error('Fal.ai: No image URL in response', ['response' => $result]);

                return null;
            }

            // Download and store the image locally
            return $this->downloadAndStoreImage($generatedImageUrl);

        } catch (\Exception $e) {
            Log::error('Fal.ai transform error', [
                'error' => $e->getMessage(),
                'image_url' => $imageUrl,
            ]);

            return null;
        }
    }

    /**
     * Transform multiple images
     *
     * @param  array  $imageUrls  Array of source image URLs
     * @param  string  $prompt  The transformation prompt
     * @param  float  $strength  How much to transform
     * @return array Array of local paths (nulls for failed transformations)
     */
    public function transformImages(array $imageUrls, string $prompt, float $strength = 0.65): array
    {
        $results = [];

        foreach ($imageUrls as $imageUrl) {
            $results[] = $this->transformImage($imageUrl, $prompt, $strength);
        }

        return $results;
    }

    /**
     * Download an image from URL and store it locally
     *
     * @param  string  $imageUrl  The image URL to download
     * @return string|null The local storage path, or null on failure
     */
    protected function downloadAndStoreImage(string $imageUrl): ?string
    {
        try {
            $response = Http::timeout(30)->get($imageUrl);

            if (! $response->successful()) {
                Log::error('Failed to download image', ['url' => $imageUrl]);

                return null;
            }

            // Generate unique filename
            $extension = 'jpg';
            $filename = 'products/'.date('Y/m/').Str::uuid().'.'.$extension;

            // Store the image
            Storage::disk('public')->put($filename, $response->body());

            // Return the public URL path
            return '/storage/'.$filename;

        } catch (\Exception $e) {
            Log::error('Failed to download and store image', [
                'error' => $e->getMessage(),
                'url' => $imageUrl,
            ]);

            return null;
        }
    }

    /**
     * Check if the service is configured and ready
     */
    public function isConfigured(): bool
    {
        return ! empty($this->apiKey);
    }
}
