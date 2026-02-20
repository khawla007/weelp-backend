<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('activity_promo_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('activities')->onDelete('cascade');
            $table->string('promo_code');
            $table->integer('max_uses');
            $table->decimal('discount_amount', 10, 2);
            // $table->enum('discount_type', ['percentage', 'fixed']);
            $table->string('discount_type');
            $table->date('valid_from');
            $table->date('valid_to');
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('activity_promo_codes');
    }
};