<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read \App\Models\Itinerary|null $itinerary
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryInformation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryInformation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryInformation query()
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class ItineraryInformation extends Model
{
    use HasFactory;

    protected $fillable = [
        'itinerary_id',
        'section_title',
        'content',
    ];

    public function itinerary(): BelongsTo
    {
        return $this->belongsTo(Itinerary::class);
    }
}
