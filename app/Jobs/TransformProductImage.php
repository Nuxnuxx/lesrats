<?php

namespace App\Jobs;

use App\Models\Product;
use App\Services\FalImageService;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

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

        $transformedPath = $falService->transformImage(
            $this->imageUrl,
            $this->prompt,
            0.65,
            $this->backgroundUrl
        );

        if (! $transformedPath) {
            Log::error('TransformProductImage: transformation failed', [
                'product_id' => $this->productId,
                'image_url' => $this->imageUrl,
            ]);

            throw new \RuntimeException('Image transformation failed for: '.$this->imageUrl);
        }

        // Apply logo overlay if requested
        if ($this->applyLogo && $product->shop->logo_path) {
            $falService->applyLogoOverlay($transformedPath, $product->shop->logo_path);
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
