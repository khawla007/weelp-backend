<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $transfer_id
 * @property bool $is_vendor
 * @property int|null $pricing_tier_id
 * @property int|null $availability_id
 * @property numeric|null $base_price
 * @property string|null $currency
 * @property string|null $price_type
 * @property numeric|null $extra_luggage_charge
 * @property numeric|null $waiting_charge
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\VendorAvailabilityTimeSlot|null $availability
 * @property-read \App\Models\VendorPricingTier|null $pricingTier
 * @property-read \App\Models\Transfer $transfer
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferPricingAvailability newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferPricingAvailability newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferPricingAvailability query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferPricingAvailability whereAvailabilityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferPricingAvailability whereBasePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferPricingAvailability whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferPricingAvailability whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferPricingAvailability whereExtraLuggageCharge($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferPricingAvailability whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferPricingAvailability whereIsVendor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferPricingAvailability wherePriceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferPricingAvailability wherePricingTierId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferPricingAvailability whereTransferId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferPricingAvailability whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferPricingAvailability whereWaitingCharge($value)
 * @mixin \Eloquent
 */
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