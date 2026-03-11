<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::rename('blog_media', 'blog_media_gallery');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('blog_media_gallery', 'blog_media');
    }
};
