<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->string('gender')->nullable()->after('phone');
            $table->string('instagram_handle')->nullable()->after('instagram_url');
            $table->string('youtube_url')->nullable()->after('instagram_handle');
            $table->string('facebook_url_profile')->nullable()->after('facebook_url');
        });
    }

    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'gender',
                'instagram_handle',
                'youtube_url',
                'facebook_url_profile',
            ]);
        });
    }
};
