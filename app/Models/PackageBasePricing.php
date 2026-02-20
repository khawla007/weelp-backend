<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackageBasePricing extends Model
{
    protected $table = 'package_base_pricing';
    
    protected $fillable = [
        'package_id', 'currency', 'availability', 
        'start_date', 'end_date'
    ];

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function variations()
    {
        return $this->hasMany(PackagePriceVariation::class, 'base_pricing_id');
    }

    public function blackoutDates()
    {
        return $this->hasMany(PackageBlackoutDate::class, 'base_pricing_id');
    }
}
