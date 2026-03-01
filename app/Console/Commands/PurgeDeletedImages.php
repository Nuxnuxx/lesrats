<?php

namespace App\Console\Commands;

use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PurgeDeletedImages extends Command
{
    protected $signature = 'images:purge-deleted';

    protected $description = 'Purge AI images deleted more than 7 days ago';

    public function handle(): void
    {
        $cutoff = Carbon::now()->subDays(7);
        $purged = 0;

        Product::whereNotNull('deleted_real_images')
            ->whereJsonLength('deleted_real_images', '>', 0)
            ->each(function (Product $product) use ($cutoff, &$purged) {
                $remaining = [];

                foreach ($product->deleted_real_images ?? [] as $item) {
                    $path = is_array($item) ? $item['path'] : $item;
                    $deletedAt = is_array($item) && isset($item['deleted_at'])
                        ? Carbon::parse($item['deleted_at'])
                        : Carbon::now(); // legacy items without timestamp: purge immediately

                    if ($deletedAt->lessThan($cutoff)) {
                        Storage::disk('public')->delete($path);
                        $purged++;
                    } else {
                        $remaining[] = $item;
                    }
                }

                $product->update(['deleted_real_images' => $remaining]);
            });

        $this->info("Purged {$purged} deleted image(s).");
    }
}
