<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItineraryInformation extends Model
{
    use HasFactory;

    protected $fillable = [
        'itinerary_id',
        'section_title',
        'content',
    ];

    public function itinerary()
    {
        return $this->belongsTo(Itinerary::class);
    }
}
