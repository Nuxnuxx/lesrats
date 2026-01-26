<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            // Ajouter colonne mode
            $table->string('mode', 20)->default('manual')->after('name');
            // Options: 'manual', 'connected'

            // Index pour requêtes fréquentes
            $table->index('mode');
        });

        // Mettre à jour les shops existantes connectées à Etsy
        DB::table('shops')
            ->whereNotNull('etsy_shop_id')
            ->update(['mode' => 'connected']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropIndex(['mode']);
            $table->dropColumn('mode');
        });
    }
};
