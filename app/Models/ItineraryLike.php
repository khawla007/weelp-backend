<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItineraryLike extends Model
{
    protected $fillable = ['itinerary_id', 'user_id'];

    public function itinerary()
    {
        return $this->belongsTo(Itinerary::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
