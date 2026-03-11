<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItineraryMediaGallery extends Model
{

    protected $table = 'itinerary_media_gallery';

    protected $fillable = [
        'itinerary_id', 'media_id', 'is_featured'
    ];

    /**
     * Scope a query to only include featured media.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function itinerary()
    {
        return $this->belongsTo(Itinerary::class);
    }

    public function media()
    {
        return $this->belongsTo(Media::class, 'media_id');
    }
}
