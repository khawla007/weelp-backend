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
        Schema::create('city_location_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('city_id')->unsigned();
            $table->decimal('latitude', 10, 6);
            $table->decimal('longitude', 10, 6);
            $table->bigInteger('population')->nullable();
            $table->string('currency')->nullable();
            $table->string('timezone');
            $table->json('language')->nullable();
            $table->json('local_cuisine')->nullable();
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
        Schema::dropIfExists('city_location_details');
    }
};
