<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfer_zone_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfer_zone_id')
                ->constrained('transfer_zones')
                ->cascadeOnDelete();
            $table->string('locatable_type');
            $table->unsignedBigInteger('locatable_id');
            $table->timestamps();

            $table->unique(
                ['transfer_zone_id', 'locatable_type', 'locatable_id'],
                'transfer_zone_locations_unique'
            );
            $table->index(['locatable_type', 'locatable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_zone_locations');
    }
};
