<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfer_zone_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_zone_id')
                ->constrained('transfer_zones')
                ->cascadeOnDelete();
            $table->foreignId('to_zone_id')
                ->constrained('transfer_zones')
                ->cascadeOnDelete();
            $table->decimal('base_price', 10, 2)->default(0);
            $table->string('currency', 10)->default('USD');
            $table->timestamps();

            $table->unique(['from_zone_id', 'to_zone_id'], 'transfer_zone_prices_pair_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_zone_prices');
    }
};
