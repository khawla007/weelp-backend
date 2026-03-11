<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class BlogMedia extends Pivot
{
    protected $table = 'blog_media_gallery';

    public $timestamps = false;

    protected $fillable = [
        'blog_id',
        'media_id',
        'is_featured',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
    ];

    /**
     * Scope to get only featured media
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function media()
    {
        return $this->belongsTo(Media::class);
    }

    public function blog()
    {
        return $this->belongsTo(Blog::class);
    }
}
