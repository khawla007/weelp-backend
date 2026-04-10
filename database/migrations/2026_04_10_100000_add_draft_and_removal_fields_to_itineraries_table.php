<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Expand approval_status enum to include draft and edit_pending_approval
        DB::statement("ALTER TABLE itineraries MODIFY COLUMN approval_status ENUM('pending_approval', 'approved', 'rejected', 'draft', 'edit_pending_approval', 'removed') NULL DEFAULT NULL");

        Schema::table('itineraries', function (Blueprint $table) {
            $table->foreignId('draft_itinerary_id')->nullable()->after('parent_itinerary_id')->constrained('itineraries')->nullOnDelete();
            $table->enum('removal_status', ['requested', 'approved', 'rejected'])->nullable()->after('likes_count');
            $table->text('removal_reason')->nullable()->after('removal_status');
        });
    }

    public function down(): void
    {
        Schema::table('itineraries', function (Blueprint $table) {
            $table->dropForeign(['draft_itinerary_id']);
            $table->dropColumn(['draft_itinerary_id', 'removal_status', 'removal_reason']);
        });

        DB::statement("ALTER TABLE itineraries MODIFY COLUMN approval_status ENUM('pending_approval', 'approved', 'rejected') NULL DEFAULT NULL");
    }
};
