<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('state_seasons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('state_id')->constrained('states')->onDelete('cascade'); // Foreign key reference
            $table->string('name'); // Name of the season (e.g., Winter, Summer)
            $table->json('months'); // Months covered (e.g., "December - February")
            $table->text('weather')->nullable(); // Description of weather conditions
            $table->json('activities')->nullable(); // Multiple activities separated by commas
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('state_seasons');
    }
};
