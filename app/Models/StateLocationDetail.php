<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StateLocationDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'state_id', 'latitude', 'longitude', 'capital_city', 'population', 'currency', 'timezone', 'language', 'local_cuisine'
    ];

    protected $casts = [
        'language' => 'array',
        'local_cuisine' => 'array',
    ];

    public function state()
    {
        return $this->belongsTo(State::class);
    }
}
