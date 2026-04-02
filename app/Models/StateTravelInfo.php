<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $state_id
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
 * @property-read \App\Models\State $state
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateTravelInfo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateTravelInfo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateTravelInfo query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateTravelInfo whereAirport($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateTravelInfo whereApartments($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateTravelInfo whereBestTimeToVisit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateTravelInfo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateTravelInfo whereHostels($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateTravelInfo whereHotels($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateTravelInfo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateTravelInfo wherePublicTransportation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateTravelInfo whereRentalCarsAvailable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateTravelInfo whereResorts($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateTravelInfo whereSafetyInformation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateTravelInfo whereStateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateTravelInfo whereTaxiAvailable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateTravelInfo whereTravelTips($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateTravelInfo whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateTravelInfo whereVisaRequirements($value)
 * @mixin \Eloquent
 */
class StateTravelInfo extends Model
{
    use HasFactory;

    protected $table = 'state_travel_info';
    protected $fillable = [
        'state_id',
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
        'public_transportation'     => 'array'
    ];

    public function state()
    {
        return $this->belongsTo(State::class);
    }
}
