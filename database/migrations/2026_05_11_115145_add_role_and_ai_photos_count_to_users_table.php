<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'beta_tester'])->default('beta_tester')->after('email');
            $table->unsignedInteger('ai_photos_count')->default(0)->after('role');
        });

        // Tous les utilisateurs existants au moment du déploiement deviennent admin.
        // Les nouveaux inscrits seront beta_tester par défaut (cf. enum default ci-dessus).
        DB::table('users')->update(['role' => 'admin']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'ai_photos_count']);
        });
    }
};
