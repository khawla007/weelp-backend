<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('post_likes');
        Schema::dropIfExists('post_item_tags');
        Schema::dropIfExists('posts');
    }

    public function down(): void
    {
        // Intentionally irreversible: creator posts were replaced by creator itineraries.
    }
};
