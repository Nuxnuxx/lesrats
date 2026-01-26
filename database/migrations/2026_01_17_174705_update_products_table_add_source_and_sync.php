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
            // Source tracking
            $table->string('source_type')->default('manual')->after('aliexpress_url'); // aliexpress, printables, manual
            $table->string('source_url')->nullable()->after('source_type');

            // Cost and profit tracking
            $table->decimal('cost_price', 10, 2)->nullable()->after('price');

            // Digital product flag (for Printables STL files)
            $table->boolean('is_digital')->default(false)->after('cost_price');

            // Tags
            $table->json('tags')->nullable()->after('description');

            // Index for filtering
            $table->index('source_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['source_type']);
            $table->dropColumn([
                'source_type',
                'source_url',
                'cost_price',
                'is_digital',
                'tags',
            ]);
        });
    }
};
