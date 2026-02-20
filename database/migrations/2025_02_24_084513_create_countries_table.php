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
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name',255)->unique();
            $table->string('code');
            $table->string('slug')->unique();
            $table->string('type')->default('country');
            $table->text('description')->nullable();
            $table->string('feature_image')->nullable();
            $table->boolean('featured_destination')->default(false); // true/false switch
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
