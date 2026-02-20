<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('city_travel_info', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('city_id')->unsigned();
            $table->text('airport')->nullable();
            $table->json('public_transportation')->nullable();
            $table->boolean('taxi_available')->default(false);
            $table->boolean('rental_cars_available')->default(false);
            $table->boolean('hotels')->default(false);
            $table->boolean('hostels')->default(false);
            $table->boolean('apartments')->default(false);
            $table->boolean('resorts')->default(false);
            $table->text('visa_requirements')->nullable();
            $table->text('best_time_to_visit')->nullable();
            $table->text('travel_tips')->nullable();
            $table->text('safety_information')->nullable();
            $table->timestamps();

            // Foreign Key Constraint
            $table->foreign('city_id')->references('id')->on('cities')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('city_travel_info');
    }
};
