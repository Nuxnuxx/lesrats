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
        Schema::create('shops', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('etsy_shop_id')->unique()->nullable();
            $table->text('etsy_access_token')->nullable();
            $table->text('etsy_refresh_token')->nullable();
            $table->timestamp('etsy_token_expires_at')->nullable();
            $table->string('currency', 3)->default('EUR');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shops');
    }
};
