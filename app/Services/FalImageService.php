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
     * Transform an image using img2img with Fal.ai
     *
     * @param string $imageUrl The source image URL
     * @param string $prompt The transformation prompt
     * @param float $strength How much to transform (0.0-1.0, lower = closer to original)
     * @return string|null The local path to the transformed image, or null on failure
     */
    public function transformImage(string $imageUrl, string $prompt, float $strength = 0.65): ?string
    {
        if (!$this->apiKey) {
            Log::warning('Fal.ai API key not configured');
            return null;
        }

        try {
            // Submit the img2img request
            $response = Http::withHeaders([
                'Authorization' => 'Key ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(120)->post('https://queue.fal.run/fal-ai/flux/dev/image-to-image', [
                'image_url' => $imageUrl,
                'prompt' => $prompt,
                'strength' => $strength,
                'num_inference_steps' => 28,
                'guidance_scale' => 3.5,
                'num_images' => 1,
                'enable_safety_checker' => true,
                'output_format' => 'jpeg',
                'sync_mode' => true, // Wait for result
            ]);

            if (!$response->successful()) {
                Log::error('Fal.ai API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $result = $response->json();

            // Get the generated image URL
            $generatedImageUrl = $result['images'][0]['url'] ?? null;

            if (!$generatedImageUrl) {
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
     * @param array $imageUrls Array of source image URLs
     * @param string $prompt The transformation prompt
     * @param float $strength How much to transform
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
     * @param string $imageUrl The image URL to download
     * @return string|null The local storage path, or null on failure
     */
    protected function downloadAndStoreImage(string $imageUrl): ?string
    {
        try {
            $response = Http::timeout(30)->get($imageUrl);

            if (!$response->successful()) {
                Log::error('Failed to download image', ['url' => $imageUrl]);
                return null;
            }

            // Generate unique filename
            $extension = 'jpg';
            $filename = 'products/' . date('Y/m/') . Str::uuid() . '.' . $extension;

            // Store the image
            Storage::disk('public')->put($filename, $response->body());

            // Return the public URL path
            return '/storage/' . $filename;

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
        return !empty($this->apiKey);
    }
}
