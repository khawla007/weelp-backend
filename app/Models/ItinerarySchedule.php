<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItinerarySchedule extends Model
{
    protected $fillable = [
        'itinerary_id', 'day'
    ];

    public function itinerary()
    {
        return $this->belongsTo(Itinerary::class);
    }

    public function activities()
    {
        return $this->hasMany(ItineraryActivity::class, 'schedule_id');
    }

    public function transfers()
    {
        return $this->hasMany(ItineraryTransfer::class, 'schedule_id');
    }
}
