<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorPricingTier extends Model {
    use HasFactory;

    protected $table = 'vendor_pricing_tiers';

    protected $fillable = ['vendor_id', 'name', 'description', 'base_price', 'price_per_km', 'min_distance', 'waiting_charge', 'night_charge_multiplier', 'peak_hour_multiplier', 'status'];

    public function vendor() {
        return $this->belongsTo(Vendor::class);
    }

    public function transferPricingAvailability()
    {
        return $this->hasMany(TransferPricingAvailability::class, 'pricing_tier_id');
    }
}
