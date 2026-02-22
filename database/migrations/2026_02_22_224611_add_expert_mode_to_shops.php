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
        Schema::table('shops', function (Blueprint $table) {
            $table->boolean('expert_mode')->default(false)->after('discount_percentage');
            $table->decimal('pricing_k', 8, 4)->default(1.0)->after('expert_mode');
            $table->decimal('pricing_t', 5, 4)->default(0.20)->after('pricing_k');
            $table->decimal('pricing_t0', 8, 2)->default(0.00)->after('pricing_t');
        });
    }

    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn(['expert_mode', 'pricing_k', 'pricing_t', 'pricing_t0']);
        });
    }
};
