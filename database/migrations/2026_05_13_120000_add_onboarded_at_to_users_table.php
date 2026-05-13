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
            $table->timestamp('onboarded_at')->nullable()->after('email_verified_at');
        });

        // Anyone who already owns a shop has effectively been onboarded — don't drop
        // them back into the wizard on first login after deploy.
        $ownerIds = DB::table('shop_memberships')
            ->where('role', 'owner')
            ->distinct()
            ->pluck('user_id');

        if ($ownerIds->isNotEmpty()) {
            DB::table('users')
                ->whereIn('id', $ownerIds)
                ->update(['onboarded_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('onboarded_at');
        });
    }
};
