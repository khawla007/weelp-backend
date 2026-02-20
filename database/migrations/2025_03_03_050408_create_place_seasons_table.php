<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('place_seasons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('place_id')->constrained('places')->onDelete('cascade');
            $table->string('name');
            $table->string('months');
            $table->text('weather');
            $table->text('activities');
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('place_seasons');
    }
};
