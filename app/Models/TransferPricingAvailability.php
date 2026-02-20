<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransferPricingAvailability extends Model
{

    use HasFactory;
    protected $fillable = [
        'transfer_id',
        'is_vendor',
        'pricing_tier_id',
        'availability_id',
        'base_price',          
        'currency',             
        'price_type',           
        'extra_luggage_charge', 
        'waiting_charge', 
    ];

    protected $casts = [
        'is_vendor' => 'boolean',
    ];
    
    // Relationship with Transfer
    public function transfer()
    {
        return $this->belongsTo(Transfer::class);
    }

    // Relationship with VendorPricingTier
    public function pricingTier()
    {
        return $this->belongsTo(VendorPricingTier::class);
    }

    // Relationship with VendorAvailabilityTimeSlot
    public function availability()
    {
        return $this->belongsTo(VendorAvailabilityTimeSlot::class);
    }
}