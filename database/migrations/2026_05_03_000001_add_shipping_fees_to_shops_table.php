<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->json('shipping_fees')->nullable()->after('shipping_fee');
        });

        DB::table('shops')->whereNotNull('shipping_fee')->get()->each(function ($shop) {
            if ((float) $shop->shipping_fee > 0) {
                DB::table('shops')->where('id', $shop->id)->update([
                    'shipping_fees' => json_encode([
                        ['label' => 'Standard', 'value' => (float) $shop->shipping_fee],
                    ]),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn('shipping_fees');
        });
    }
};
