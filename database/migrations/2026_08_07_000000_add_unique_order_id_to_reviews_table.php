<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('reviews')
            ->whereNotNull('order_id')
            ->select('order_id')
            ->groupBy('order_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('order_id')
            ->each(function (int $orderId): void {
                $duplicateIds = DB::table('reviews')
                    ->where('order_id', $orderId)
                    ->orderByDesc('created_at')
                    ->orderByDesc('id')
                    ->pluck('id')
                    ->skip(1);

                DB::table('reviews')
                    ->whereIn('id', $duplicateIds)
                    ->update(['order_id' => null]);
            });

        Schema::table('reviews', function (Blueprint $table): void {
            $table->unique('order_id', 'reviews_order_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table): void {
            $table->dropUnique('reviews_order_id_unique');
        });
    }
};
