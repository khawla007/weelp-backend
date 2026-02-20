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
        Schema::create('transfer_schedules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('transfer_id')
                ->constrained('transfers')
                ->onDelete('cascade');

            $table->boolean('is_vendor')->default(true);

            // Availability Type: always available, specific date, custom schedule
            $table->string('availability_type')->nullable();

            // Available days (comma-separated: e.g., Mon,Tue,Wed)
            $table->string('available_days')->nullable();

            // time slots (JSON array)
            $table->json('time_slots')->nullable(); 

            // Blackout dates (JSON array)
            $table->json('blackout_dates')->nullable();

            // Minimum lead time (hours)
            $table->integer('minimum_lead_time')->nullable();

            // Maximum passengers
            $table->integer('maximum_passengers')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfer_schedules');
    }
};
