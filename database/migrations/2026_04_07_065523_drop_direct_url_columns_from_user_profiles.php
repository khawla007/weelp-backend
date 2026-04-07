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
        Schema::table('user_profiles', function (Blueprint $table) {
            $columnsToDrop = [
                'facebook_url',
                'facebook_url_profile',
                'instagram_url',
                'instagram_handle',
                'linkedin_url',
                'youtube_url',
                'myspace_url',
                'pinterest_url',
            ];

            $existing = Schema::getColumnListing('user_profiles');
            $toDrop = array_intersect($columnsToDrop, $existing);

            if (!empty($toDrop)) {
                $table->dropColumn($toDrop);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->string('facebook_url')->nullable()->after('phone');
            $table->string('facebook_url_profile')->nullable()->after('facebook_url');
            $table->string('instagram_url')->nullable()->after('facebook_url_profile');
            $table->string('instagram_handle')->nullable()->after('instagram_url');
            $table->string('linkedin_url')->nullable()->after('instagram_handle');
            $table->string('youtube_url')->nullable()->after('linkedin_url');
            $table->string('myspace_url')->nullable()->after('youtube_url');
            $table->string('pinterest_url')->nullable()->after('myspace_url');
        });
    }
};
