<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItineraryAddon extends Model
{
    protected $table = 'itinerary_addons';

    protected $fillable = [
        'itinerary_id',
        'addon_id',
    ];

    // Relations
    public function itinerary()
    {
        return $this->belongsTo(Itinerary::class, 'itinerary_id');
    }

    public function addon()
    {
        return $this->belongsTo(Addon::class, 'addon_id');
    }
}
