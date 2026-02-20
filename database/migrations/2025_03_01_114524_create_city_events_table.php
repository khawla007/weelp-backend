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
        Schema::create('city_events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('city_id')->unsigned();
            $table->string('name'); // Event name
            $table->json('type'); // Type of event (Festival, Conference, etc.)
            $table->date('date'); // Date and time of the event
            $table->string('location')->nullable(); // Event location
            $table->text('description')->nullable(); // Event description
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
        Schema::dropIfExists('city_events');
    }
};
