<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('itinerary_meta', function (Blueprint $table) {
            $table->foreignId('draft_itinerary_id')->nullable()->after('parent_itinerary_id')->constrained('itineraries')->nullOnDelete();
            $table->enum('removal_status', ['requested', 'approved', 'rejected'])->nullable()->after('likes_count');
            $table->text('removal_reason')->nullable()->after('removal_status');
        });
    }

    public function down(): void
    {
        Schema::table('itinerary_meta', function (Blueprint $table) {
            $table->dropForeign(['draft_itinerary_id']);
            $table->dropColumn(['draft_itinerary_id', 'removal_status', 'removal_reason']);
        });
    }
};
