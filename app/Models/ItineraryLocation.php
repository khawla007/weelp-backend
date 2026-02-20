<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItineraryLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'itinerary_id',
        'city_id',
    ];

    public function itinerary()
    {
        return $this->belongsTo(Itinerary::class);
    }

    public function city() {
        
        return $this->belongsTo(City::class, 'city_id');
    }
}
