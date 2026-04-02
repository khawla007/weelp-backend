<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItineraryFaq extends Model
{
    use HasFactory;

    protected $fillable = [
        'itinerary_id',
        'question_number',
        'question',
        'answer',
    ];

    public function itinerary()
    {
        return $this->belongsTo(Itinerary::class);
    }
}
