<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $itinerary_id
 * @property int $city_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\City $city
 * @property-read \App\Models\Itinerary $itinerary
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryLocation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryLocation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryLocation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryLocation whereCityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryLocation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryLocation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryLocation whereItineraryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryLocation whereUpdatedAt($value)
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class ItineraryLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'itinerary_id',
        'city_id',
    ];

    public function itinerary(): BelongsTo
    {
        return $this->belongsTo(Itinerary::class);
    }

    public function city(): BelongsTo
    {

        return $this->belongsTo(City::class, 'city_id');
    }
}
