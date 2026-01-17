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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('set null');
            
            // Etsy references
            $table->string('etsy_listing_id')->nullable();
            $table->string('etsy_transaction_id')->nullable();
            
            // Item details (copied from product at time of order)
            $table->string('title');
            $table->unsignedInteger('quantity')->default(1);
            
            // Financial
            $table->decimal('price', 10, 2); // Price per unit
            $table->decimal('cost', 10, 2)->default(0); // Cost per unit
            $table->decimal('profit', 10, 2)->default(0); // Profit per unit
            
            // Product snapshot (in case product is deleted)
            $table->string('sku')->nullable();
            $table->string('source_type')->nullable(); // aliexpress, printables, manual
            $table->string('source_url')->nullable(); // For "Order from supplier" link
            $table->boolean('is_digital')->default(false);
            
            $table->timestamps();
            
            // Indexes
            $table->index('order_id');
            $table->index('product_id');
            $table->index('etsy_listing_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
