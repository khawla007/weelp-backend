<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('place_faqs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('place_id')->constrained('places')->onDelete('cascade');
            $table->integer('question_number')->autoIncrement(false);
            $table->text('question');
            $table->text('answer');
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('place_faqs');
    }
};
