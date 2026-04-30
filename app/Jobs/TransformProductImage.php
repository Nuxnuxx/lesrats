<?php

namespace App\Jobs;

use App\Models\Product;
use App\Services\FalImageService;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TransformProductImage implements ShouldQueue
{
    use Batchable, Queueable;

    public int $tries = 2;

    public int $timeout = 300; // 5 minutes max per image

    public function __construct(
        public int $productId,
        public string $imageUrl,
        public string $prompt,
        public ?string $backgroundUrl = null,
        public bool $applyLogo = false,
        public ?string $falApiKey = null,
        public bool $onlyLogo = false,
        public string $model = 'v1',
        public ?string $logoPath = null,
    ) {}

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $product = Product::find($this->productId);

        if (! $product) {
            Log::warning('TransformProductImage: product not found', ['product_id' => $this->productId]);

            return;
        }

        $falService = new FalImageService($this->falApiKey);

        if ($this->onlyLogo) {
            // Only Logo mode: download image and apply logo without AI
            $transformedPath = $falService->downloadImage($this->imageUrl);

            if (! $transformedPath) {
                Log::error('TransformProductImage: download failed (only_logo)', [
                    'product_id' => $this->productId,
                    'image_url' => $this->imageUrl,
                ]);

                throw new \RuntimeException('Image download failed for: '.$this->imageUrl);
            }

            $effectiveLogo = $this->logoPath
                ?? $product->shop->default_ai_logo
                ?? ($product->shop->ai_logos[0]['path'] ?? null)
                ?? $product->shop->logo_path;
            if ($effectiveLogo) {
                $falService->applyLogoOverlay($transformedPath, $effectiveLogo);
            }
        } else {
            // Normal AI mode — resolve logo URL to pass as reference image to the AI (same as background)
            $logoUrl = null;
            if ($this->applyLogo) {
                $effectiveLogo = $this->logoPath
                    ?? $product->shop->default_ai_logo
                    ?? ($product->shop->ai_logos[0]['path'] ?? null)
                    ?? $product->shop->logo_path;
                if ($effectiveLogo) {
                    $logoUrl = Storage::disk('public')->url($effectiveLogo);
                }
            }

            $transformedPath = $this->model === 'v2'
                ? $falService->transformImageV2($this->imageUrl, $this->prompt, $this->backgroundUrl, $logoUrl)
                : $falService->transformImage($this->imageUrl, $this->prompt, 0.65, $this->backgroundUrl, $logoUrl);

            if (! $transformedPath) {
                Log::error('TransformProductImage: transformation failed', [
                    'product_id' => $this->productId,
                    'image_url' => $this->imageUrl,
                ]);

                throw new \RuntimeException('Image transformation failed for: '.$this->imageUrl);
            }
        }

        // Append to real_images atomically (refresh to get latest state from other jobs)
        // With database queue driver, jobs run sequentially by default per worker,
        // so race conditions are unlikely, but we refresh to be safe.
        $product->refresh();
        $realImages = $product->real_images ?? [];
        $realImages[] = $transformedPath;
        $product->update(['real_images' => $realImages]);

        Log::info('TransformProductImage: success', [
            'product_id' => $this->productId,
            'transformed_path' => $transformedPath,
        ]);
    }
}
