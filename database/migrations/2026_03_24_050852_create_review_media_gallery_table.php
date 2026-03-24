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
        Schema::create('review_media_gallery', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained('reviews')->onDelete('cascade');
            $table->foreignId('media_id')->constrained('media')->onDelete('cascade');
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('review_id');
        });

        // Add indexes on reviews table for query performance
        Schema::table('reviews', function (Blueprint $table) {
            $table->index(['item_type', 'item_id']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex(['item_type', 'item_id']);
            $table->dropIndex(['status']);
        });

        Schema::dropIfExists('review_media_gallery');
    }
};
