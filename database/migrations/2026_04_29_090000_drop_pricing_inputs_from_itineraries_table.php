<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('itineraries', function (Blueprint $table) {
            $table->dropColumn(['travel_date', 'adults', 'children', 'infants']);
        });
    }

    public function down(): void
    {
        Schema::table('itineraries', function (Blueprint $table) {
            $table->date('travel_date')->nullable()->after('private_itinerary');
            $table->unsignedInteger('adults')->default(1)->after('travel_date');
            $table->unsignedInteger('children')->default(0)->after('adults');
            $table->unsignedInteger('infants')->default(0)->after('children');
        });
    }
};
