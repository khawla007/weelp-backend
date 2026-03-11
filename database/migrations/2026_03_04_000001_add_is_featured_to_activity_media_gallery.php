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
        Schema::table('activity_media_gallery', function (Blueprint $table) {
            // Add is_featured column
            $table->boolean('is_featured')->default(false)->after('media_id');
        });

        // Migrate existing data: mark first image as featured for activities that don't have one
        // This will be done via a seeder
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_media_gallery', function (Blueprint $table) {
            $table->dropColumn('is_featured');
        });
    }
};
