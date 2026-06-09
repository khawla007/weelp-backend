<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('activity_seo')) {
            return;
        }

        Schema::create('activity_seo', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('activity_id')->constrained('activities')->cascadeOnDelete();
            $table->string('meta_title', 255)->nullable();
            $table->text('meta_description')->nullable();
            $table->text('keywords')->nullable();
            $table->text('og_image_url')->nullable();
            $table->text('canonical_url')->nullable();
            $table->string('schema_type', 255)->nullable();
            $table->json('schema_data')->nullable();
            $table->longText('head_code')->nullable();
            $table->longText('body_code')->nullable();
            $table->longText('footer_code')->nullable();
            $table->timestamps();
            $table->unique('activity_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_seo');
    }
};
