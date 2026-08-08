<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('itineraries', function (Blueprint $table): void {
            $table->softDeletes();
        });

        Schema::table('itinerary_meta', function (Blueprint $table): void {
            $table->timestamp('publication_requested_at')->nullable()->after('removal_reason');
            $table->text('publication_rejection_reason')->nullable()->after('publication_requested_at');
        });
    }

    public function down(): void
    {
        Schema::table('itinerary_meta', function (Blueprint $table): void {
            $table->dropColumn(['publication_requested_at', 'publication_rejection_reason']);
        });

        Schema::table('itineraries', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });
    }
};
