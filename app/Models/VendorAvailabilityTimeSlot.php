<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $vendor_id
 * @property int $vehicle_id
 * @property string $date
 * @property string $start_time
 * @property string $end_time
 * @property int $max_bookings
 * @property numeric $price_multiplier
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TransferPricingAvailability> $transferAvailability
 * @property-read int|null $transfer_availability_count
 * @property-read \App\Models\VendorVehicle $vehicle
 * @property-read \App\Models\Vendor $vendor
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorAvailabilityTimeSlot newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorAvailabilityTimeSlot newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorAvailabilityTimeSlot query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorAvailabilityTimeSlot whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorAvailabilityTimeSlot whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorAvailabilityTimeSlot whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorAvailabilityTimeSlot whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorAvailabilityTimeSlot whereMaxBookings($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorAvailabilityTimeSlot wherePriceMultiplier($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorAvailabilityTimeSlot whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorAvailabilityTimeSlot whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorAvailabilityTimeSlot whereVehicleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorAvailabilityTimeSlot whereVendorId($value)
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class VendorAvailabilityTimeSlot extends Model
{
    use HasFactory;

    protected $table = 'vendor_availability_time_slots';

    protected $fillable = ['vendor_id', 'vehicle_id', 'date', 'start_time', 'end_time', 'max_bookings', 'price_multiplier'];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(VendorVehicle::class);
    }

    public function transferAvailability(): HasMany
    {
        return $this->hasMany(TransferPricingAvailability::class, 'availability_id');
    }
}
