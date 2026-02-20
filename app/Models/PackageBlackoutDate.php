<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackageBlackoutDate extends Model
{
    protected $fillable = [
        'base_pricing_id', 'date', 'reason'
    ];

    public function basePricing()
    {
        return $this->belongsTo(PackageBasePricing::class, 'base_pricing_id');
    }
}
