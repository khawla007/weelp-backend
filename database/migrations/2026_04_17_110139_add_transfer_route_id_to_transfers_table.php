<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transfers', function (Blueprint $table) {
            $table->foreignId('transfer_route_id')
                ->nullable()
                ->after('transfer_type')
                ->constrained('transfer_routes')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transfers', function (Blueprint $table) {
            $table->dropForeign(['transfer_route_id']);
            $table->dropColumn('transfer_route_id');
        });
    }
};
