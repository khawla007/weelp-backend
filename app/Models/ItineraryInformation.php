<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property-read \App\Models\Itinerary|null $itinerary
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryInformation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryInformation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryInformation query()
 *
 * @mixin \Eloquent
 */
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
