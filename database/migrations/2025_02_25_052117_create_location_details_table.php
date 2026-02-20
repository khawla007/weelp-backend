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
        Schema::create('country_location_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained('countries')->onDelete('cascade'); // Foreign Key
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('capital_city')->nullable();
            $table->bigInteger('population')->nullable();
            $table->string('currency')->nullable();
            $table->string('timezone')->nullable();
            // $table->string('language')->nullable();
            // $table->string('local_cuisine')->nullable();
            $table->json('language')->nullable();
            $table->json('local_cuisine')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('country_location_details');
    }
};
