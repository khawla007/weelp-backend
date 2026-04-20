<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('place_media_gallery', function (Blueprint $table) {
            $table->boolean('is_featured')->default(false)->after('media_id');
        });

        // Migrate existing data: if place has feature_image, find matching media_id and set is_featured = true
        DB::statement('
            UPDATE place_media_gallery pmg
            INNER JOIN places p ON pmg.place_id = p.id
            INNER JOIN media m ON pmg.media_id = m.id
            SET pmg.is_featured = 1
            WHERE p.feature_image = m.url
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('place_media_gallery', function (Blueprint $table) {
            $table->dropColumn('is_featured');
        });
    }
};
