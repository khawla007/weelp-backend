<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $vendor_id
 * @property string $name
 * @property string|null $description
 * @property numeric $base_price
 * @property numeric $price_per_km
 * @property int $min_distance
 * @property numeric $waiting_charge
 * @property numeric $night_charge_multiplier
 * @property numeric $peak_hour_multiplier
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TransferPricingAvailability> $transferPricingAvailability
 * @property-read int|null $transfer_pricing_availability_count
 * @property-read \App\Models\Vendor $vendor
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorPricingTier newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorPricingTier newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorPricingTier query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorPricingTier whereBasePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorPricingTier whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorPricingTier whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorPricingTier whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorPricingTier whereMinDistance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorPricingTier whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorPricingTier whereNightChargeMultiplier($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorPricingTier wherePeakHourMultiplier($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorPricingTier wherePricePerKm($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorPricingTier whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorPricingTier whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorPricingTier whereVendorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorPricingTier whereWaitingCharge($value)
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class VendorPricingTier extends Model
{
    use HasFactory;

    protected $table = 'vendor_pricing_tiers';

    protected $fillable = ['vendor_id', 'name', 'description', 'base_price', 'price_per_km', 'min_distance', 'waiting_charge', 'night_charge_multiplier', 'peak_hour_multiplier', 'status'];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function transferPricingAvailability(): HasMany
    {
        return $this->hasMany(TransferPricingAvailability::class, 'pricing_tier_id');
    }
}
