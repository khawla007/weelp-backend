<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('activity_pricing', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('activities')->onDelete('cascade');
            $table->decimal('regular_price', 10, 2);
            $table->string('currency', 10);
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('activity_pricing');
    }
};