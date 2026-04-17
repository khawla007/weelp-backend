<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfer_routes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('origin_type');
            $table->unsignedBigInteger('origin_id');
            $table->string('destination_type');
            $table->unsignedBigInteger('destination_id');
            $table->foreignId('from_zone_id')
                ->nullable()
                ->constrained('transfer_zones')
                ->nullOnDelete();
            $table->foreignId('to_zone_id')
                ->nullable()
                ->constrained('transfer_zones')
                ->nullOnDelete();
            $table->decimal('distance_km', 8, 2)->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_popular')->default(false);
            $table->timestamps();

            $table->index(['origin_type', 'origin_id']);
            $table->index(['destination_type', 'destination_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_routes');
    }
};
