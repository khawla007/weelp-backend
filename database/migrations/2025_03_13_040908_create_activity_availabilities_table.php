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
        Schema::create('activity_availabilities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('activity_id'); // Foreign key to activities table
            $table->boolean('date_based_activity')->default(false);
            $table->date('start_date')->nullable(); // If date-based activity is true
            $table->date('end_date')->nullable();   // If date-based activity is true
            $table->boolean('quantity_based_activity')->default(false);
            $table->integer('max_quantity')->nullable(); // If quantity-based activity is true
            $table->timestamps();
    
            // Foreign key constraint
            $table->foreign('activity_id')->references('id')->on('activities')->onDelete('cascade');
        });
    
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_availabilities');
    }
};
