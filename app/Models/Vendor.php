<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string $email
 * @property string $phone
 * @property string $address
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\VendorAvailabilityTimeSlot> $availabilityTimeSlots
 * @property-read int|null $availability_time_slots_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\VendorDriver> $drivers
 * @property-read int|null $drivers_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\VendorPricingTier> $pricingTiers
 * @property-read int|null $pricing_tiers_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\VendorRoute> $routes
 * @property-read int|null $routes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TransferVendorRoute> $transferVendor
 * @property-read int|null $transfer_vendor_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\VendorVehicle> $vehicles
 * @property-read int|null $vehicles_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vendor newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vendor newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vendor query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vendor whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vendor whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vendor whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vendor whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vendor whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vendor whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vendor wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vendor whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vendor whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Vendor extends Model
{
    use HasFactory;

    protected $table = 'vendors'; // Custom table name

    protected $fillable = ['name', 'description', 'email', 'phone', 'address', 'status'];

    public function routes()
    {
        return $this->hasMany(VendorRoute::class);
    }

    public function pricingTiers()
    {
        return $this->hasMany(VendorPricingTier::class);
    }

    public function vehicles()
    {
        return $this->hasMany(VendorVehicle::class);
    }

    public function drivers()
    {
        return $this->hasMany(VendorDriver::class);
    }

    public function transferVendor()
    {
        return $this->hasMany(TransferVendorRoute::class, 'vendor_id');
    }

    public function availabilityTimeSlots()
    {
        return $this->hasMany(VendorAvailabilityTimeSlot::class);
    }
}
