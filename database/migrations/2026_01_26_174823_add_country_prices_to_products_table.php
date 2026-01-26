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
            $table->json('country_prices')->nullable()->after('cost_price');
            $table->decimal('price_us', 10, 2)->nullable()->after('country_prices');
            $table->decimal('price_other', 10, 2)->nullable()->after('price_us');
            $table->decimal('margin_us', 5, 2)->nullable()->after('price_other');
            $table->decimal('margin_other', 5, 2)->nullable()->after('margin_us');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['country_prices', 'price_us', 'price_other', 'margin_us', 'margin_other']);
        });
    }
};
