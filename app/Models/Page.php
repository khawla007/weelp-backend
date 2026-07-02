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
    ];

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }
}
