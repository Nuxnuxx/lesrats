<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('main_color')->nullable()->after('etsy_category');
            $table->string('secondary_color')->nullable()->after('main_color');
            $table->json('materials')->nullable()->after('secondary_color');
            $table->boolean('has_pockets')->nullable()->after('materials');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['main_color', 'secondary_color', 'materials', 'has_pockets']);
        });
    }
};
