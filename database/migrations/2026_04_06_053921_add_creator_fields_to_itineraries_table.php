<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('itineraries', function (Blueprint $table) {
            $table->foreignId('creator_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->after('creator_id')->constrained('users')->nullOnDelete();
            $table->foreignId('parent_itinerary_id')->nullable()->after('user_id')->constrained('itineraries')->nullOnDelete();
            $table->enum('approval_status', ['pending_approval', 'approved', 'rejected'])->nullable()->after('private_itinerary');
            $table->unsignedInteger('views_count')->default(0)->after('approval_status');
            $table->unsignedInteger('likes_count')->default(0)->after('views_count');
        });
    }

    public function down(): void
    {
        Schema::table('itineraries', function (Blueprint $table) {
            $table->dropForeign(['creator_id']);
            $table->dropForeign(['user_id']);
            $table->dropForeign(['parent_itinerary_id']);
            $table->dropColumn([
                'creator_id',
                'user_id',
                'parent_itinerary_id',
                'approval_status',
                'views_count',
                'likes_count',
            ]);
        });
    }
};
