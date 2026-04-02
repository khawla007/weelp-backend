<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $id
 * @property int $blog_id
 * @property int $media_id
 * @property bool $is_featured
 * @property-read \App\Models\Blog $blog
 * @property-read \App\Models\Media $media
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlogMedia featured()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlogMedia newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlogMedia newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlogMedia query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlogMedia whereBlogId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlogMedia whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlogMedia whereIsFeatured($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlogMedia whereMediaId($value)
 *
 * @mixin \Eloquent
 */
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
