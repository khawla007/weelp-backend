<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_payments', function (Blueprint $table): void {
            $table->decimal('refunded_amount', 12, 2)->default(0)->after('total_amount');
            $table->enum('payment_status', ['pending', 'partial', 'paid', 'failed', 'cancelled', 'partially_refunded', 'refunded'])->change();
        });
    }

    public function down(): void
    {
        DB::table('order_payments')
            ->where('payment_status', 'partially_refunded')
            ->update(['payment_status' => 'paid']);

        Schema::table('order_payments', function (Blueprint $table): void {
            $table->enum('payment_status', ['pending', 'partial', 'paid', 'failed', 'cancelled', 'refunded'])->change();
            $table->dropColumn('refunded_amount');
        });
    }
};
