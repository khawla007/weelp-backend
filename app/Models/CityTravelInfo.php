<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $city_id
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
 * @property-read \App\Models\City $city
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityTravelInfo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityTravelInfo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityTravelInfo query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityTravelInfo whereAirport($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityTravelInfo whereApartments($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityTravelInfo whereBestTimeToVisit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityTravelInfo whereCityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityTravelInfo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityTravelInfo whereHostels($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityTravelInfo whereHotels($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityTravelInfo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityTravelInfo wherePublicTransportation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityTravelInfo whereRentalCarsAvailable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityTravelInfo whereResorts($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityTravelInfo whereSafetyInformation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityTravelInfo whereTaxiAvailable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityTravelInfo whereTravelTips($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityTravelInfo whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityTravelInfo whereVisaRequirements($value)
 * @mixin \Eloquent
 */
class CityTravelInfo extends Model
{
    use HasFactory;

    protected $table = 'city_travel_info';
    protected $fillable = [
        'city_id', 'airport', 'public_transportation', 'taxi_available', 
        'rental_cars_available', 'hotels', 'hostels', 'apartments', 
        'resorts', 'visa_requirements', 'best_time_to_visit', 
        'travel_tips', 'safety_information'
    ];

    protected $casts = [
        'public_transportation'     => 'array'
    ];

    public function city()
    {
        return $this->belongsTo(City::class);
    }
}
