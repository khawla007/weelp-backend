<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('itinerary_schedules', function (Blueprint $table) {
            $table->id(); // Auto Increment Primary Key
            $table->unsignedBigInteger('itinerary_id');
            $table->integer('day');
            $table->timestamps();

            // Foreign Key Constraint
            $table->foreign('itinerary_id')->references('id')->on('itineraries')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('itinerary_schedules');
    }
};
