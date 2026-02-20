<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('activity_seasonal_pricing', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('activities')->onDelete('cascade');
            $table->boolean('enable_seasonal_pricing')->default(false);
            $table->string('season_name');
            $table->date('season_start');
            $table->date('season_end');
            $table->decimal('season_price', 10, 2);
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('activity_seasonal_pricing');
    }
};