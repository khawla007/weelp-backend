<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE user_notifications MODIFY COLUMN `type` ENUM(
            'application_approved',
            'application_rejected',
            'itinerary_approved',
            'itinerary_rejected',
            'new_booking',
            'itinerary_edit_approved',
            'itinerary_edit_rejected',
            'itinerary_removal_approved',
            'itinerary_removal_rejected'
        ) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE user_notifications MODIFY COLUMN `type` ENUM(
            'application_approved',
            'application_rejected',
            'itinerary_approved',
            'itinerary_rejected',
            'new_booking'
        ) NOT NULL");
    }
};
