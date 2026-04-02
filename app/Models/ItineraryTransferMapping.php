<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItineraryTransferMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'itinerary_id',
        'transfer_id',
    ];

    public function itinerary()
    {
        return $this->belongsTo(Itinerary::class);
    }

    public function transfer()
    {
        return $this->belongsTo(Transfer::class);
    }
}
