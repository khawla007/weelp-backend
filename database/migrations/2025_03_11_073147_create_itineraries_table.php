<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('itineraries', function (Blueprint $table) {
            $table->id(); // Auto Increment Primary Key
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('item_type')->default('itinerary'); 
            $table->boolean('featured_itinerary')->default(false);
            $table->boolean('private_itinerary')->default(false);
            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('itineraries');
    }
};
