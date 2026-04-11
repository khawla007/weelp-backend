<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('itinerary_meta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('itinerary_id')->unique()->constrained('itineraries')->cascadeOnDelete();
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('parent_itinerary_id')->nullable()->constrained('itineraries')->nullOnDelete();
            $table->enum('status', ['pending', 'approved', 'rejected', 'draft', 'edit_pending', 'deleted'])->nullable();
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('likes_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('itinerary_meta');
    }
};
