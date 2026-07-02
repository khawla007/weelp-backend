<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    protected $fillable = [
        'title',
        'slug',
        'content',
        'excerpt',
        'status',
        'published_at',
        'hero_background_image_url',
        'hero_heading',
        'hero_text',
        'hero_button_label',
        'hero_button_url',
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
        'meta_title',
        'meta_description',
        'keywords',
        'og_image_url',
        'canonical_url',
        'schema_type',
        'schema_data',
        'head_code',
        'body_code',
        'footer_code',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'schema_data' => 'array',
        'hero_overlay_opacity' => 'float',
        'hero_heading_bold' => 'boolean',
        'hero_heading_italic' => 'boolean',
        'hero_heading_underline' => 'boolean',
        'hero_text_bold' => 'boolean',
        'hero_text_italic' => 'boolean',
        'hero_text_underline' => 'boolean',
    ];

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }
}
