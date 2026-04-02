<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property-read \App\Models\Itinerary|null $itinerary
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryFaq newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryFaq newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryFaq query()
 *
 * @mixin \Eloquent
 */
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
