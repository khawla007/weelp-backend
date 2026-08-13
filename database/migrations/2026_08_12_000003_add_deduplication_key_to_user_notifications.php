<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_notifications', function (Blueprint $table): void {
            $table->string('deduplication_key', 191)->nullable()->after('user_id');
            $table->unique('deduplication_key', 'user_notifications_deduplication_key_unique');
        });
    }

    public function down(): void
    {
        Schema::table('user_notifications', function (Blueprint $table): void {
            $table->dropUnique('user_notifications_deduplication_key_unique');
            $table->dropColumn('deduplication_key');
        });
    }
};
