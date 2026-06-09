<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('global_script_settings', function (Blueprint $table) {
            $table->id();
            $table->text('head_code')->nullable();
            $table->text('body_code')->nullable();
            $table->text('footer_code')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('global_script_settings');
    }
};
