<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class ActivityMediaGallery extends Model
{
    protected $table = 'activity_media_gallery';

    protected $fillable = [
        'activity_id', 'media_id', 'is_featured'
    ];

    /**
     * Scope a query to only include featured media.
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    public function media()
    {
        return $this->belongsTo(Media::class, 'media_id');
    }
}
