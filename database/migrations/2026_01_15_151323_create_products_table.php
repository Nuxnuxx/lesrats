<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->onDelete('cascade');

            // Basic product information
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->integer('quantity')->default(0);
            $table->string('sku')->nullable();

            // Etsy integration
            $table->string('etsy_listing_id')->nullable()->unique();
            $table->string('etsy_state')->nullable(); // active, inactive, draft
            $table->timestamp('etsy_synced_at')->nullable();

            // AliExpress integration (for future use)
            $table->string('aliexpress_product_id')->nullable();
            $table->string('aliexpress_url')->nullable();

            // Images (store as JSON array)
            $table->json('images')->nullable();

            // Product status
            $table->boolean('is_active')->default(true);
            $table->boolean('auto_sync')->default(false); // Auto sync with Etsy

            $table->timestamps();

            // Indexes
            $table->index(['shop_id', 'is_active']);
            $table->index('etsy_listing_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
