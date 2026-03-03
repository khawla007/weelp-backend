<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('state_media_gallery', function (Blueprint $table) {
            $table->boolean('is_featured')->default(false)->after('media_id');
        });

        // Migrate existing data: if state has feature_image, find matching media_id and set is_featured = true
        DB::statement('
            UPDATE state_media_gallery smg
            INNER JOIN states s ON smg.state_id = s.id
            INNER JOIN media m ON smg.media_id = m.id
            SET smg.is_featured = 1
            WHERE s.feature_image = m.url
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('state_media_gallery', function (Blueprint $table) {
            $table->dropColumn('is_featured');
        });
    }
};
