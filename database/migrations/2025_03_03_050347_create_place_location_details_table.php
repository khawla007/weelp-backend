<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('place_location_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('place_id')->constrained('places')->onDelete('cascade');
            $table->decimal('latitude', 10, 6);
            $table->decimal('longitude', 10, 6);
            $table->bigInteger('population')->nullable();
            $table->string('currency')->nullable();
            $table->string('timezone');
            $table->string('language');
            $table->text('local_cuisine')->nullable();
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('place_location_details');
    }
};

