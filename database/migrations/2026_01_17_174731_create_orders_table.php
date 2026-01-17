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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->onDelete('cascade');
            
            // Etsy order reference
            $table->string('etsy_receipt_id')->unique();
            
            // Customer information
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            
            // Financial information
            $table->decimal('total_price', 12, 2);
            $table->decimal('total_cost', 12, 2)->default(0);
            $table->decimal('total_profit', 12, 2)->default(0);
            $table->string('currency', 3)->default('EUR');
            
            // Order status
            $table->string('status')->default('new'); // new, ordered, shipped, delivered, completed
            
            // Timestamps for status changes
            $table->timestamp('ordered_at')->nullable(); // When ordered from supplier
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            
            // Etsy raw data (for reference)
            $table->json('etsy_data')->nullable();
            
            // Shipping address
            $table->json('shipping_address')->nullable();
            
            // Notes
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['shop_id', 'status']);
            $table->index(['shop_id', 'created_at']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
