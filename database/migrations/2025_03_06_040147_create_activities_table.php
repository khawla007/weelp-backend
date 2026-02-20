<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('item_type')->default('activity'); // Fixed value 'package'
            $table->text('short_description')->nullable();
            $table->boolean('featured_activity')->default(false);
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('activities');
    }
};

