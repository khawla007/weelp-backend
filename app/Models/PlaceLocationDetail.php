<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlaceLocationDetail extends Model {
    use HasFactory;

    protected $fillable = [
        'place_id', 'latitude', 'longitude', 'population', 'currency', 
        'timezone', 'language', 'local_cuisine'
    ];

    protected $casts = [
        'language' => 'array',
        'local_cuisine' => 'array',
    ];

    public function place() {
        return $this->belongsTo(Place::class);
    }
}
