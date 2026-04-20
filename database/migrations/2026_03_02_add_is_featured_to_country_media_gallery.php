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
        Schema::table('country_media_gallery', function (Blueprint $table) {
            $table->boolean('is_featured')->default(false)->after('media_id');
        });

        // Migrate existing data: if country has feature_image, find matching media_id and set is_featured = true
        if (DB::getDriverName() === 'mysql') {
            DB::statement('
                UPDATE country_media_gallery cmg
                INNER JOIN countries c ON cmg.country_id = c.id
                INNER JOIN media m ON cmg.media_id = m.id
                SET cmg.is_featured = 1
                WHERE c.feature_image = m.url
            ');
        } else {
            DB::statement('
                UPDATE country_media_gallery
                SET is_featured = 1
                WHERE id IN (
                    SELECT cmg.id FROM country_media_gallery cmg
                    JOIN countries c ON cmg.country_id = c.id
                    JOIN media m ON cmg.media_id = m.id
                    WHERE c.feature_image = m.url
                )
            ');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('country_media_gallery', function (Blueprint $table) {
            $table->dropColumn('is_featured');
        });
    }
};
