<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $vendor_id
 * @property string $vehicle_type
 * @property int $capacity
 * @property string $make
 * @property string $model
 * @property int $year
 * @property string $license_plate
 * @property string|null $features
 * @property string $status
 * @property string|null $last_maintenance
 * @property string|null $next_maintenance
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\VendorAvailabilityTimeSlot> $availabilityTimeSlots
 * @property-read int|null $availability_time_slots_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\VendorDriver> $drivers
 * @property-read int|null $drivers_count
 * @property-read \App\Models\Vendor $vendor
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorVehicle newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorVehicle newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorVehicle query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorVehicle whereCapacity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorVehicle whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorVehicle whereFeatures($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorVehicle whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorVehicle whereLastMaintenance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorVehicle whereLicensePlate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorVehicle whereMake($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorVehicle whereModel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorVehicle whereNextMaintenance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorVehicle whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorVehicle whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorVehicle whereVehicleType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorVehicle whereVendorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorVehicle whereYear($value)
 * @mixin \Eloquent
 */
class VendorVehicle extends Model {
    use HasFactory;

    protected $table = 'vendor_vehicles';

    protected $fillable = ['vendor_id', 'vehicle_type', 'capacity', 'make', 'model', 'year', 'license_plate', 'features', 'status', 'last_maintenance', 'next_maintenance'];

    public function vendor() {
        return $this->belongsTo(Vendor::class);
    }

    public function availabilityTimeSlots()
    {
        return $this->hasMany(VendorAvailabilityTimeSlot::class, 'vehicle_id');
    }

    public function drivers() {
        return $this->hasMany(VendorDriver::class, 'assigned_vehicle_id');
    }
}
