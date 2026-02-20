<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('activity_last_minute_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('activities')->onDelete('cascade');
            $table->boolean('enabled')->default(false);
            $table->integer('days_before_start');
            $table->decimal('discount_amount', 10, 2);
            // $table->enum('discount_type', ['percentage', 'fixed']);
            $table->string('discount_type');
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('activity_last_minute_discounts');
    }
};