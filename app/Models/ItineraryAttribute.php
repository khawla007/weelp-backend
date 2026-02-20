<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItineraryAttribute extends Model
{
    protected $fillable = [
        'itinerary_id', 'attribute_id', 'attribute_value'
    ];

    public function itinerary()
    {
        return $this->belongsTo(Itinerary::class);
    }

    public function attribute() {
        return $this->belongsTo(Attribute::class, 'attribute_id');
    }
}
