<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->string('display_style', 16)->default('inline')->after('link');
            $table->string('image_url', 2048)->nullable()->after('display_style');
            $table->string('coupon_code', 64)->nullable()->after('image_url');
        });
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropColumn(['display_style', 'image_url', 'coupon_code']);
        });
    }
};
