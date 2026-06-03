<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE user_notifications MODIFY COLUMN `type` ENUM(
                'application_approved','application_rejected','itinerary_approved','itinerary_rejected',
                'new_booking','itinerary_edit_approved','itinerary_edit_rejected',
                'itinerary_removal_approved','itinerary_removal_rejected','custom'
            ) NOT NULL");
            return;
        }
        // sqlite/others: drop the enum CHECK by widening to a plain string so 'custom' inserts.
        Schema::table('user_notifications', function (Blueprint $table) {
            $table->string('type')->change();
        });
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE user_notifications MODIFY COLUMN `type` ENUM(
                'application_approved','application_rejected','itinerary_approved','itinerary_rejected',
                'new_booking','itinerary_edit_approved','itinerary_edit_rejected',
                'itinerary_removal_approved','itinerary_removal_rejected'
            ) NOT NULL");
        }
    }
};
