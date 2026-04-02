<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $itinerary_id
 * @property int $date_based_itinerary
 * @property string|null $start_date
 * @property string|null $end_date
 * @property int $quantity_based_itinerary
 * @property int|null $max_quantity
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Itinerary $itinerary
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryAvailability newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryAvailability newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryAvailability query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryAvailability whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryAvailability whereDateBasedItinerary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryAvailability whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryAvailability whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryAvailability whereItineraryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryAvailability whereMaxQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryAvailability whereQuantityBasedItinerary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryAvailability whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryAvailability whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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
