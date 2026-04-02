<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property-read \App\Models\Itinerary|null $itinerary
 * @property-read \App\Models\Transfer|null $transfer
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryTransferMapping newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryTransferMapping newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryTransferMapping query()
 * @mixin \Eloquent
 */
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
