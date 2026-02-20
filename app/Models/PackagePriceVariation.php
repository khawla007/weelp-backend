<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackagePriceVariation extends Model
{
    protected $fillable = [
        'base_pricing_id', 'name', 'regular_price', 'sale_price', 
        'max_guests', 'description'
    ];

    public function basePricing()
    {
        return $this->belongsTo(PackageBasePricing::class, 'base_pricing_id');
    }
}
