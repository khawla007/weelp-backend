<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('itinerary_seo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('itinerary_id')->constrained('itineraries')->onDelete('cascade');
            $table->string('meta_title', 255);
            $table->text('meta_description')->nullable();
            $table->text('keywords')->nullable();
            $table->text('og_image_url')->nullable();
            $table->text('canonical_url')->nullable();
            $table->string('schema_type', 255)->nullable();
            $table->json('schema_data')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('itinerary_seo');
    }
};
