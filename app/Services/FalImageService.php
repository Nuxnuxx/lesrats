<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

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
            // Build image URLs array (Nano Banana supports up to 14 reference images)
            $imageUrls = [$imageUrl];

            // Add background image if provided
            if ($backgroundUrl) {
                // If it's a local URL, upload it to Fal.ai storage first
                if ($this->isLocalUrl($backgroundUrl)) {
                    $uploadedUrl = $this->uploadLocalFileToFal($backgroundUrl);
                    if ($uploadedUrl) {
                        $imageUrls[] = $uploadedUrl;
                        Log::info('Background uploaded to Fal.ai', ['original' => $backgroundUrl, 'uploaded' => $uploadedUrl]);
                    } else {
                        Log::warning('Failed to upload background to Fal.ai', ['background_url' => $backgroundUrl]);
                    }
                } else {
                    $imageUrls[] = $backgroundUrl;
                    Log::info('Using background reference', ['background_url' => $backgroundUrl]);
                }
            }

            // Build the request payload for Nano Banana
            $payload = [
                'image_urls' => $imageUrls,
                'prompt' => $prompt,
                'num_images' => 1,
                'output_format' => 'png',
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

            // Upscale the image for HD quality
            $upscaledUrl = $this->upscaleImage($generatedImageUrl);

            // Download and store the image locally (upscaled if available, otherwise original)
            return $this->downloadAndStoreImage($upscaledUrl ?? $generatedImageUrl);

        } catch (\Exception $e) {
            Log::error('Fal.ai transform error', [
                'error' => $e->getMessage(),
                'image_url' => $imageUrl,
            ]);

            return null;
        }
    }

    /**
     * Check if a URL is a local/localhost URL
     */
    protected function isLocalUrl(string $url): bool
    {
        return str_contains($url, 'localhost') || str_contains($url, '127.0.0.1');
    }

    /**
     * Convert a local file to a base64 data URI that Fal.ai can use directly
     *
     * @param  string  $localUrl  The local URL (e.g., http://localhost:8000/storage/backgrounds/...)
     * @return string|null The base64 data URI, or null on failure
     */
    protected function uploadLocalFileToFal(string $localUrl): ?string
    {
        try {
            // Extract the storage path from the local URL
            // e.g., http://localhost:8000/storage/backgrounds/shop_5/file.jpg -> backgrounds/shop_5/file.jpg
            $path = preg_replace('#^https?://[^/]+/storage/#', '', $localUrl);

            if (! $path || $path === $localUrl) {
                Log::error('Could not extract path from local URL', ['url' => $localUrl]);

                return null;
            }

            // Read the file from local storage
            if (! Storage::disk('public')->exists($path)) {
                Log::error('Local file not found', ['path' => $path]);

                return null;
            }

            $fileContent = Storage::disk('public')->get($path);
            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

            // Determine MIME type based on extension
            $mimeTypes = [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
            ];
            $mimeType = $mimeTypes[$extension] ?? 'image/jpeg';

            // Return as base64 data URI (Fal.ai accepts data URIs directly)
            return 'data:'.$mimeType.';base64,'.base64_encode($fileContent);

        } catch (\Exception $e) {
            Log::error('Error converting local file to data URI', [
                'error' => $e->getMessage(),
                'url' => $localUrl,
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
            $extension = 'png';
            $filename = 'products/'.date('Y/m/').Str::uuid().'.'.$extension;

            // Store the image
            Storage::disk('public')->put($filename, $response->body());

            // Return the public URL (works with local storage and R2)
            return Storage::disk('public')->url($filename);

        } catch (\Exception $e) {
            Log::error('Failed to download and store image', [
                'error' => $e->getMessage(),
                'url' => $imageUrl,
            ]);

            return null;
        }
    }

    /**
     * Upscale an image using Real-ESRGAN via Fal.ai
     *
     * @param  string  $imageUrl  The image URL to upscale
     * @return string|null The upscaled image URL, or null on failure
     */
    protected function upscaleImage(string $imageUrl): ?string
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Key '.$this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(120)->post('https://fal.run/fal-ai/esrgan', [
                'image_url' => $imageUrl,
                'scale' => 2,
                'model' => 'RealESRGAN_x4plus',
                'output_format' => 'png',
            ]);

            if (! $response->successful()) {
                Log::warning('Fal.ai upscale error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $result = $response->json();

            return $result['image']['url'] ?? null;

        } catch (\Exception $e) {
            Log::warning('Fal.ai upscale failed, using original image', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Apply a shop logo overlay on a locally stored image.
     *
     * @param  string  $imageUrl  The public URL of the stored image
     * @param  string  $logoPath  The storage-relative path to the logo (e.g. "shop-logos/abc.png")
     * @return bool Whether the overlay was successfully applied
     */
    public function applyLogoOverlay(string $imageUrl, string $logoPath): bool
    {
        $imageTmp = null;
        $logoTmp = null;

        try {
            $disk = Storage::disk('public');

            // Resolve storage-relative path from public URL.
            // Handles both local (/storage/file.png) and CDN URLs (no /storage/ prefix).
            $urlPath = parse_url($imageUrl, PHP_URL_PATH);
            $imageRelativePath = Str::after($urlPath, '/storage/');
            if ($imageRelativePath === $urlPath) {
                // /storage/ not found in path (CDN URL) — strip leading slash
                $imageRelativePath = ltrim($urlPath, '/');
            }

            if (! $disk->exists($imageRelativePath)) {
                Log::warning('Logo overlay: product image not found', ['path' => $imageRelativePath, 'url' => $imageUrl]);

                return false;
            }

            if (! $disk->exists($logoPath)) {
                Log::warning('Logo overlay: logo file not found', ['path' => $logoPath]);

                return false;
            }

            // Write to temp files so Intervention Image can read them regardless of storage driver
            $ext = pathinfo($imageRelativePath, PATHINFO_EXTENSION) ?: 'png';
            $imageTmp = sys_get_temp_dir().'/img_'.Str::random(16).'.'.$ext;
            $logoTmp = sys_get_temp_dir().'/logo_'.Str::random(16).'.png';

            file_put_contents($imageTmp, $disk->get($imageRelativePath));
            file_put_contents($logoTmp, $disk->get($logoPath));

            $manager = new ImageManager(new Driver);

            $image = $manager->read($imageTmp);
            $imageWidth = $image->width();

            // Resize logo to 15% of image width (scaleDown won't upscale small logos)
            $logoTargetWidth = (int) round($imageWidth * 0.15);
            $logo = $manager->read($logoTmp);
            $logo->scaleDown(width: $logoTargetWidth);

            // 3% padding from edges
            $padding = (int) round($imageWidth * 0.03);

            $image->place(
                element: $logo,
                position: 'top-right',
                offset_x: $padding,
                offset_y: $padding,
            );

            // Write back via disk (works with local, S3, R2)
            $disk->put($imageRelativePath, (string) $image->toPng());

            Log::info('Logo overlay applied', [
                'image' => $imageRelativePath,
                'logo' => $logoPath,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Logo overlay failed', [
                'error' => $e->getMessage(),
                'image_url' => $imageUrl,
                'logo_path' => $logoPath,
            ]);

            return false;
        } finally {
            if ($imageTmp && file_exists($imageTmp)) {
                @unlink($imageTmp);
            }
            if ($logoTmp && file_exists($logoTmp)) {
                @unlink($logoTmp);
            }
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
