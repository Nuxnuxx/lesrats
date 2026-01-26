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

            // AliExpress integration
            $table->string('aliexpress_product_id')->nullable();
            $table->string('aliexpress_url')->nullable();

            // Images (store as JSON array)
            $table->json('images')->nullable();

            // Product status
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Indexes
            $table->index(['shop_id', 'is_active']);
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
