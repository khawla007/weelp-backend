<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_locations', function (Blueprint $table) {
            $table->unsignedBigInteger('place_id')->nullable()->after('city_id');
            $table->foreign('place_id')->references('id')->on('places')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('activity_locations', function (Blueprint $table) {
            $table->dropForeign(['place_id']);
            $table->dropColumn('place_id');
        });
    }
};
