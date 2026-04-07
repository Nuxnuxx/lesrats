<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\ContentOptimizerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RegenerateProductContent extends Command
{
    protected $signature = 'products:regenerate
                            {--shop= : Only regenerate products for this shop ID}
                            {--limit= : Maximum number of products to process}
                            {--product= : Regenerate a specific product by ID}';

    protected $description = 'Regenerate AI titles, descriptions and tags for existing products using Groq Vision';

    public function handle(): void
    {
        $optimizer = new ContentOptimizerService;

        $query = Product::query()->whereNotNull('images')->with('shop');

        if ($this->option('product')) {
            $query->where('id', (int) $this->option('product'));
        } else {
            if ($this->option('shop')) {
                $query->where('shop_id', (int) $this->option('shop'));
            }

            if ($this->option('limit')) {
                $query->limit((int) $this->option('limit'));
            }
        }

        $products = $query->get();
        $total = $products->count();

        $this->info("Processing {$total} products...");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($products as $product) {
            try {
                $images = $product->images ?? [];
                $is3DPrint = $product->source_type === 'printables';

                // Groq Vision — analyze first image
                $visualContext = null;
                if (! empty($images)) {
                    $visualContext = $optimizer->analyzeProductImages($images);
                    Log::info('Visual context from image analysis', [
                        'product_id' => $product->id,
                        'context' => $visualContext ?? 'NULL - vision failed',
                    ]);
                }

                // Regenerate title (use current title as base)
                $newTitle = $optimizer->optimizeTitle(
                    $product->title,
                    $is3DPrint ? '3D Print' : null,
                    null,
                    $visualContext
                );

                // Regenerate description
                $newDescription = $optimizer->optimizeDescription(
                    $product->title,
                    $product->description,
                    [],
                    $is3DPrint,
                    null,
                    $visualContext
                );

                // Regenerate tags
                $shop = $product->shop;
                $newTags = $optimizer->selectRelevantTags(
                    $newTitle,
                    $newDescription,
                    $shop->available_tags ?? [],
                    $is3DPrint,
                    $visualContext
                );

                // Analyze product attributes (color, materials)
                $attributes = $optimizer->analyzeProductAttributes(
                    $newTitle,
                    $visualContext,
                    $is3DPrint
                );

                // Update product
                $product->update([
                    'title' => $newTitle,
                    'description' => $newDescription,
                    'tags' => $newTags,
                    'main_color' => $attributes['main_color'],
                    'secondary_color' => $attributes['secondary_color'],
                    'materials' => $attributes['materials'],
                ]);

                Log::info('Product content regenerated', [
                    'product_id' => $product->id,
                    'new_title' => $newTitle,
                    'visual_context' => $visualContext,
                ]);

            } catch (\Exception $e) {
                Log::error('Failed to regenerate product', [
                    'product_id' => $product->id,
                    'error' => $e->getMessage(),
                ]);
                $this->warn("\nFailed: product #{$product->id} — {$e->getMessage()}");
            }

            // Avoid Groq rate limits
            sleep(1);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Done!');
    }
}
