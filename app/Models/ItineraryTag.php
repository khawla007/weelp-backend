<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItineraryTag extends Model
{
    protected $fillable = [
        'itinerary_id', 'tag_id'
    ];

    public function itinerary()
    {
        return $this->belongsTo(Itinerary::class);
    }

    public function tag()
    {
        return $this->belongsTo(Tag::class, 'tag_id');
    }
}
