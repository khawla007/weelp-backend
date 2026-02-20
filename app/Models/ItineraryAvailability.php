<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItineraryAvailability extends Model
{
    use HasFactory;

    protected $fillable = [
        'itinerary_id',
        'date_based_itinerary',
        'start_date',
        'end_date',
        'quantity_based_itinerary',
        'max_quantity',
    ];

    public function itinerary()
    {
        return $this->belongsTo(Itinerary::class);
    }
}
