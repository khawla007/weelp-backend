<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItineraryBasePricing extends Model
{
    protected $table = 'itinerary_base_pricing';
    
    protected $fillable = [
        'itinerary_id', 'currency', 'availability', 
        'start_date', 'end_date'
    ];

    public function itinerary()
    {
        return $this->belongsTo(Itinerary::class);
    }

    public function variations()
    {
        return $this->hasMany(ItineraryPriceVariation::class, 'base_pricing_id');
    }

    public function blackoutDates()
    {
        return $this->hasMany(ItineraryBlackoutDate::class, 'base_pricing_id');
    }
}
