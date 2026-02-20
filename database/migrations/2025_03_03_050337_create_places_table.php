<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('places', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->string('slug')->unique();
            $table->string('type')->default('place');
            $table->bigInteger('city_id')->unsigned();
            $table->text('description')->nullable();
            $table->string('feature_image')->nullable();
            $table->boolean('featured_destination')->default(false);
            $table->timestamps();

            $table->foreign('city_id')->references('id')->on('cities')->onDelete('cascade');
        });
    }

    public function down() {
        Schema::dropIfExists('places');
    }
};
