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
            $table->text('ai_title_prompt')->nullable()->after('auto_sync_enabled');
            $table->text('ai_description_prompt')->nullable()->after('ai_title_prompt');
            $table->text('ai_image_prompt')->nullable()->after('ai_description_prompt');
            $table->boolean('ai_image_enabled')->default(false)->after('ai_image_prompt');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn([
                'ai_title_prompt',
                'ai_description_prompt',
                'ai_image_prompt',
                'ai_image_enabled',
            ]);
        });
    }
};
