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
        Schema::create('package_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained('package_schedules')->onDelete('cascade');
            $table->foreignId('transfer_id')->constrained('transfers')->onDelete('cascade');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->boolean('included')->default(false);
            $table->string('pickup_location');
            $table->string('dropoff_location');
            $table->integer('pax')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('package_transfers');
    }
};
