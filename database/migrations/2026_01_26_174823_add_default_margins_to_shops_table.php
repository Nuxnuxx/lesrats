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
            $table->decimal('default_margin_us', 5, 2)->default(2.5)->after('available_tags');
            $table->decimal('default_margin_other', 5, 2)->default(2.5)->after('default_margin_us');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn(['default_margin_us', 'default_margin_other']);
        });
    }
};
