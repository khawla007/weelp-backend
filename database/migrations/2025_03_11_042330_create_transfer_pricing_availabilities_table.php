<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transfer_pricing_availabilities', function (Blueprint $table) {
            $table->id();
    
            $table->foreignId('transfer_id')->constrained('transfers')->onDelete('cascade');
    
            // Is Vendor (default true)
            $table->boolean('is_vendor')->default(true);
    
            // Nullable foreign keys
            $table->unsignedBigInteger('pricing_tier_id')->nullable();
            $table->unsignedBigInteger('availability_id')->nullable();

            // Foreign key constraints
            $table->foreign('pricing_tier_id')
                ->references('id')
                ->on('vendor_pricing_tiers')
                ->onDelete('cascade');

            $table->foreign('availability_id')
                ->references('id')
                ->on('vendor_availability_time_slots')
                ->onDelete('cascade');
    
            // New columns for non-vendor pricing
            $table->decimal('base_price', 10, 2)->nullable();
            $table->string('currency')->nullable();
            $table->enum('price_type', ['per_person', 'per_vehicle'])->nullable();
            $table->decimal('extra_luggage_charge', 10, 2)->nullable();
            $table->decimal('waiting_charge', 10, 2)->nullable();
    
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfer_pricing_availabilities');
    }
};
