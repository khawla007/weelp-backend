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
            $table->enum('payment_status', ['pending', 'partial', 'paid', 'failed', 'cancelled', 'refunded'])->change();
        });
    }

    public function down(): void
    {
        DB::table('order_payments')->whereIn('payment_status', ['failed', 'cancelled'])->update(['payment_status' => 'pending']);
        Schema::table('order_payments', function (Blueprint $table): void {
            $table->enum('payment_status', ['pending', 'partial', 'paid', 'refunded'])->change();
        });
    }
};
