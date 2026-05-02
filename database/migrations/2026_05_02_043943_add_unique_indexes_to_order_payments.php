<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->dedupeColumn('payment_intent_id');
        $this->dedupeColumn('stripe_session_id');

        Schema::table('order_payments', function (Blueprint $table) {
            $table->unique('payment_intent_id', 'order_payments_payment_intent_id_unique');
            $table->unique('stripe_session_id', 'order_payments_stripe_session_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('order_payments', function (Blueprint $table) {
            $table->dropUnique('order_payments_payment_intent_id_unique');
            $table->dropUnique('order_payments_stripe_session_id_unique');
        });
    }

    private function dedupeColumn(string $column): void
    {
        $duplicates = DB::table('order_payments')
            ->select($column)
            ->whereNotNull($column)
            ->groupBy($column)
            ->havingRaw('COUNT(*) > 1')
            ->pluck($column);

        foreach ($duplicates as $value) {
            $rows = DB::table('order_payments')
                ->where($column, $value)
                ->orderByRaw("CASE payment_status WHEN 'paid' THEN 0 WHEN 'partial' THEN 1 WHEN 'refunded' THEN 2 ELSE 3 END")
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->get(['id', 'order_id']);

            $keep = $rows->shift();
            $removeIds = $rows->pluck('id')->all();

            if ($removeIds === []) {
                continue;
            }

            $orphanedOrderIds = $rows
                ->where('order_id', '!==', $keep->order_id)
                ->pluck('order_id')
                ->unique()
                ->all();

            if ($orphanedOrderIds !== []) {
                Log::error('order_payments dedupe leaves orders without a payment row — manual review required', [
                    'column' => $column,
                    'value' => $value,
                    'kept_order_id' => $keep->order_id,
                    'orphaned_order_ids' => $orphanedOrderIds,
                ]);
            }

            DB::table('order_payments')->whereIn('id', $removeIds)->delete();

            Log::warning('order_payments dedupe', [
                'column' => $column,
                'value' => $value,
                'kept_id' => $keep->id,
                'removed_ids' => $removeIds,
            ]);
        }
    }
};
