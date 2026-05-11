<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Restore the invitation_codes table. The previous "drop" migration
        // stays in history; this re-creates the schema going forward (prod-safe).
        if (! Schema::hasTable('invitation_codes')) {
            Schema::create('invitation_codes', function (Blueprint $table) {
                $table->id();
                $table->string('code', 32)->unique();
                $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
                $table->foreignId('used_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('used_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('invitation_codes');
    }
};
