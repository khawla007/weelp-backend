<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CityLocationDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'city_id', 'latitude', 'longitude', 'population', 'currency', 
        'timezone', 'language', 'local_cuisine'
    ];

    protected $casts = [
        'language' => 'array',
        'local_cuisine' => 'array',
    ];

    public function city()
    {
        return $this->belongsTo(City::class);
    }
}
