<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItineraryBlackoutDate extends Model
{
    protected $fillable = [
        'base_pricing_id', 'date', 'reason'
    ];

    public function basePricing()
    {
        return $this->belongsTo(ItineraryBasePricing::class, 'base_pricing_id');
    }
}
