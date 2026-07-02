<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table): void {
            $table->text('hero_background_image_url')->nullable()->after('published_at');
            $table->string('hero_heading')->nullable()->after('hero_background_image_url');
            $table->text('hero_text')->nullable()->after('hero_heading');
            $table->string('hero_button_label')->nullable()->after('hero_text');
            $table->text('hero_button_url')->nullable()->after('hero_button_label');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table): void {
            $table->dropColumn([
                'hero_background_image_url',
                'hero_heading',
                'hero_text',
                'hero_button_label',
                'hero_button_url',
            ]);
        });
    }
};
