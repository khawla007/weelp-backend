<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('itinerary_transfers', function (Blueprint $table) {
            $table->unsignedInteger('bag_count')->default(0)->after('pax');
            $table->unsignedInteger('waiting_minutes')->default(0)->after('bag_count');
        });
    }

    public function down(): void
    {
        Schema::table('itinerary_transfers', function (Blueprint $table) {
            $table->dropColumn(['bag_count', 'waiting_minutes']);
        });
    }
};
