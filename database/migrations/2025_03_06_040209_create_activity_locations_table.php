<?php

// use Illuminate\Database\Migrations\Migration;
// use Illuminate\Database\Schema\Blueprint;
// use Illuminate\Support\Facades\Schema;

// return new class extends Migration {
//     public function up() {
//         Schema::create('activity_locations', function (Blueprint $table) {
//             $table->id();
//             $table->foreignId('activity_id')->constrained('activities')->onDelete('cascade');
//             $table->foreignId('city_id')->constrained('cities')->onDelete('cascade');
//             $table->string('location_type');
//             $table->timestamps();
//         });
//     }

//     public function down() {
//         Schema::dropIfExists('activity_locations');
//     }
// };


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('activity_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('activities')->onDelete('cascade');
            // $table->enum('location_type', ['primary', 'additional']);
            $table->enum('location_type', ['primary', 'additional'])->default('additional');
            $table->foreignId('city_id')->constrained('cities')->onDelete('cascade');
            $table->string('location_label')->nullable(); 
            $table->integer('duration')->nullable(); 
            $table->timestamps();
        
            // Ek activity ke liye ek hi primary location allow karne ke liye unique constraint
            // $table->unique(['activity_id', 'location_type'], 'unique_primary_location')->where('location_type', 'primary');
        });
    }

    public function down() {
        Schema::dropIfExists('activity_locations');
    }
};
