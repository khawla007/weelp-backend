<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItinerarySeo extends Model
{

    protected $table = 'itinerary_seo';

    protected $fillable = [
        'itinerary_id', 'meta_title', 'meta_description', 
        'keywords', 'og_image_url', 'canonical_url', 
        'schema_type', 'schema_data'
    ];

    protected $casts = [
        'schema_data' => 'array'
    ];

    public function itinerary()
    {
        return $this->belongsTo(Itinerary::class);
    }
}
