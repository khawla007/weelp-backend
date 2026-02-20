<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('state_travel_info', function (Blueprint $table) {
            $table->id();
            $table->foreignId('state_id')->constrained('states')->onDelete('cascade'); // Foreign key reference
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
        });
    }

    public function down()
    {
        Schema::dropIfExists('state_travel_info');
    }
};
