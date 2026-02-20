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
        Schema::create('states', function (Blueprint $table) {
            $table->bigIncrements('id'); // Primary Key
            $table->string('name'); // State Name
            $table->string('code')->nullable();
            $table->string('slug')->unique(); // Unique Slug
            $table->string('type')->default('state');
            $table->unsignedBigInteger('country_id'); // Foreign Key
            $table->text('description')->nullable(); // Optional Description
            $table->string('feature_image')->nullable(); // Image URL
            $table->boolean('featured_destination')->default(false); // Featured State (true/false)
            $table->timestamps(); // Laravel Default Timestamps

            // Foreign Key Constraint
            $table->foreign('country_id')->references('id')->on('countries')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('states');
    }
};
