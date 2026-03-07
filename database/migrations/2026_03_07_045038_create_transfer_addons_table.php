<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfer_addons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transfer_id');
            $table->unsignedBigInteger('addon_id');
            $table->timestamps();

            $table->foreign('transfer_id')->references('id')->on('transfers')->onDelete('cascade');
            $table->foreign('addon_id')->references('id')->on('addons')->onDelete('cascade');
            $table->unique(['transfer_id', 'addon_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_addons');
    }
};
