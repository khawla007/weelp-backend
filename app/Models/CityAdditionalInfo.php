<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CityAdditionalInfo extends Model
{
    use HasFactory;

    // protected $table = 'city_additional_info';
    protected $fillable = [
        'city_id', 'title', 'content'
    ];

    public function city()
    {
        return $this->belongsTo(City::class);
    }
}
