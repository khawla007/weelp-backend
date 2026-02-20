<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('itinerary_transfers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('schedule_id');
            $table->unsignedBigInteger('transfer_id');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->boolean('included')->default(false);
            $table->text('pickup_location')->nullable();
            $table->text('dropoff_location')->nullable();
            $table->integer('pax')->nullable();
            $table->timestamps();

            // Foreign Key Constraints
            $table->foreign('schedule_id')->references('id')->on('itinerary_schedules')->onDelete('cascade');
            $table->foreign('transfer_id')->references('id')->on('transfers')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('itinerary_transfers');
    }
};
