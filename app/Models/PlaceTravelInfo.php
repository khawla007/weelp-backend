<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $place_id
 * @property string|null $airport
 * @property array<array-key, mixed>|null $public_transportation
 * @property int $taxi_available
 * @property int $rental_cars_available
 * @property int $hotels
 * @property int $hostels
 * @property int $apartments
 * @property int $resorts
 * @property string|null $visa_requirements
 * @property string|null $best_time_to_visit
 * @property string|null $travel_tips
 * @property string|null $safety_information
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Place $place
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceTravelInfo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceTravelInfo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceTravelInfo query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceTravelInfo whereAirport($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceTravelInfo whereApartments($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceTravelInfo whereBestTimeToVisit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceTravelInfo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceTravelInfo whereHostels($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceTravelInfo whereHotels($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceTravelInfo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceTravelInfo wherePlaceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceTravelInfo wherePublicTransportation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceTravelInfo whereRentalCarsAvailable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceTravelInfo whereResorts($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceTravelInfo whereSafetyInformation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceTravelInfo whereTaxiAvailable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceTravelInfo whereTravelTips($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceTravelInfo whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceTravelInfo whereVisaRequirements($value)
 * @mixin \Eloquent
 */
class PlaceTravelInfo extends Model {
    use HasFactory;

    protected $table = 'place_travel_info';
    protected $fillable = [
        'place_id', 'airport', 'public_transportation', 'taxi_available', 
        'rental_cars_available', 'hotels', 'hostels', 'apartments', 
        'resorts', 'visa_requirements', 'best_time_to_visit', 
        'travel_tips', 'safety_information'
    ];

    protected $casts = [
        'public_transportation'     => 'array'
    ];

    public function place() {
        return $this->belongsTo(Place::class);
    }
}
