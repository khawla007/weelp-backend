<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
