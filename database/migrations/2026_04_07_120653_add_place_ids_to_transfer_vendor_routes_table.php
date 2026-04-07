<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transfer_vendor_routes', function (Blueprint $table) {
            $table->unsignedBigInteger('pickup_place_id')->nullable()->after('dropoff_location');
            $table->unsignedBigInteger('dropoff_place_id')->nullable()->after('pickup_place_id');
            $table->foreign('pickup_place_id')->references('id')->on('places')->onDelete('set null');
            $table->foreign('dropoff_place_id')->references('id')->on('places')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('transfer_vendor_routes', function (Blueprint $table) {
            $table->dropForeign(['pickup_place_id']);
            $table->dropForeign(['dropoff_place_id']);
            $table->dropColumn(['pickup_place_id', 'dropoff_place_id']);
        });
    }
};
