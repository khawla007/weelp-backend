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
        Schema::create('vendor_driver_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            $table->foreignId('driver_id')->constrained('vendor_drivers')->onDelete('cascade'); // Reference vendor_drivers
            $table->foreignId('vehicle_id')->constrained('vendor_vehicles')->onDelete('cascade'); // Reference vendor_vehicles
            $table->date('date');
            $table->string('shift');
            $table->time('time');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_driver_schedules');
    }
};
