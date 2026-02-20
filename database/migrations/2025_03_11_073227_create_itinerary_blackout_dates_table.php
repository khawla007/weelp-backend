<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('itinerary_blackout_dates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('base_pricing_id');
            $table->date('date');
            $table->text('reason')->nullable();
            $table->timestamps();

            // Foreign Key Constraint
            $table->foreign('base_pricing_id')->references('id')->on('itinerary_base_pricing')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('itinerary_blackout_dates');
    }
};
