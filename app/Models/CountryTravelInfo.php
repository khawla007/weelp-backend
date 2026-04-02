<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $country_id
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
 * @property-read \App\Models\Country $country
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryTravelInfo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryTravelInfo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryTravelInfo query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryTravelInfo whereAirport($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryTravelInfo whereApartments($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryTravelInfo whereBestTimeToVisit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryTravelInfo whereCountryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryTravelInfo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryTravelInfo whereHostels($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryTravelInfo whereHotels($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryTravelInfo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryTravelInfo wherePublicTransportation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryTravelInfo whereRentalCarsAvailable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryTravelInfo whereResorts($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryTravelInfo whereSafetyInformation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryTravelInfo whereTaxiAvailable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryTravelInfo whereTravelTips($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryTravelInfo whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryTravelInfo whereVisaRequirements($value)
 *
 * @mixin \Eloquent
 */
class CountryTravelInfo extends Model
{
    use HasFactory;

    protected $table = 'country_travel_info';

    protected $fillable = [
        'country_id',
        'airport',
        'public_transportation',
        'taxi_available',
        'rental_cars_available',
        'hotels',
        'hostels',
        'apartments',
        'resorts',
        'visa_requirements',
        'best_time_to_visit',
        'travel_tips',
        'safety_information',
    ];

    protected $casts = [
        'public_transportation' => 'array',
    ];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}
