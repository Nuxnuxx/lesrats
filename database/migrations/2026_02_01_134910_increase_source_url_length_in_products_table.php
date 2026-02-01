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
        Schema::table('products', function (Blueprint $table) {
            // Change source_url and aliexpress_url to TEXT to handle long URLs with tracking params
            $table->text('source_url')->nullable()->change();
            $table->text('aliexpress_url')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('source_url')->nullable()->change();
            $table->string('aliexpress_url')->nullable()->change();
        });
    }
};
