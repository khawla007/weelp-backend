<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table): void {
            $table->string('hero_overlay_color')->nullable()->after('hero_button_url');
            $table->decimal('hero_overlay_opacity', 5, 2)->nullable()->after('hero_overlay_color');
            $table->string('hero_content_vertical_position')->nullable()->after('hero_overlay_opacity');
            $table->string('hero_heading_size')->nullable()->after('hero_content_vertical_position');
            $table->string('hero_heading_color')->nullable()->after('hero_heading_size');
            $table->string('hero_heading_align')->nullable()->after('hero_heading_color');
            $table->boolean('hero_heading_bold')->nullable()->after('hero_heading_align');
            $table->boolean('hero_heading_italic')->nullable()->after('hero_heading_bold');
            $table->boolean('hero_heading_underline')->nullable()->after('hero_heading_italic');
            $table->string('hero_text_size')->nullable()->after('hero_heading_underline');
            $table->string('hero_text_color')->nullable()->after('hero_text_size');
            $table->string('hero_text_align')->nullable()->after('hero_text_color');
            $table->boolean('hero_text_bold')->nullable()->after('hero_text_align');
            $table->boolean('hero_text_italic')->nullable()->after('hero_text_bold');
            $table->boolean('hero_text_underline')->nullable()->after('hero_text_italic');
            $table->string('hero_button_radius')->nullable()->after('hero_text_underline');
            $table->string('hero_button_border_width')->nullable()->after('hero_button_radius');
            $table->string('hero_button_padding')->nullable()->after('hero_button_border_width');
            $table->string('hero_button_margin')->nullable()->after('hero_button_padding');
            $table->string('hero_button_text_color')->nullable()->after('hero_button_margin');
            $table->string('hero_button_bg_color')->nullable()->after('hero_button_text_color');
            $table->string('hero_button_border_color')->nullable()->after('hero_button_bg_color');
            $table->string('hero_button_text_size')->nullable()->after('hero_button_border_color');
            $table->string('hero_button_align')->nullable()->after('hero_button_text_size');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table): void {
            $table->dropColumn([
                'hero_overlay_color',
                'hero_overlay_opacity',
                'hero_content_vertical_position',
                'hero_heading_size',
                'hero_heading_color',
                'hero_heading_align',
                'hero_heading_bold',
                'hero_heading_italic',
                'hero_heading_underline',
                'hero_text_size',
                'hero_text_color',
                'hero_text_align',
                'hero_text_bold',
                'hero_text_italic',
                'hero_text_underline',
                'hero_button_radius',
                'hero_button_border_width',
                'hero_button_padding',
                'hero_button_margin',
                'hero_button_text_color',
                'hero_button_bg_color',
                'hero_button_border_color',
                'hero_button_text_size',
                'hero_button_align',
            ]);
        });
    }
};
