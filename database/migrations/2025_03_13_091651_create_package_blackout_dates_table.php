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
        Schema::create('package_blackout_dates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('base_pricing_id')->constrained('package_base_pricing')->onDelete('cascade');
            $table->date('date');
            $table->text('reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('package_blackout_dates');
    }
};
