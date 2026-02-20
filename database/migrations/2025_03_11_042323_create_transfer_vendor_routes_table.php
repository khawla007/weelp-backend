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
        Schema::create('transfer_vendor_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfer_id')->constrained('transfers')->onDelete('cascade');
            $table->boolean('is_vendor')->default(true);
    
            // Vendor True Column
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->onDelete('cascade');
            $table->foreignId('route_id')->nullable()->constrained('vendor_routes')->onDelete('cascade');
    
            // Vendor False Column
            $table->string('pickup_location')->nullable();
            $table->string('dropoff_location')->nullable();
            $table->string('vehicle_type')->nullable();
            $table->text('inclusion')->nullable();
    
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfer_vendor_routes');
    }
};
