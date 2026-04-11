<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Only rename if the source table exists (in case it was already created as user_notifications)
        if (Schema::hasTable('notifications') && !Schema::hasTable('user_notifications')) {
            Schema::rename('notifications', 'user_notifications');
        }
    }

    public function down(): void
    {
        Schema::rename('user_notifications', 'notifications');
    }
};
