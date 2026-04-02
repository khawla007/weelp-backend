<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $vendor_id
 * @property int $driver_id
 * @property int $vehicle_id
 * @property string $date
 * @property string $shift
 * @property string $time
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\VendorDriver $driver
 * @property-read \App\Models\VendorVehicle $vehicle
 * @property-read \App\Models\Vendor $vendor
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorDriverSchedule newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorDriverSchedule newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorDriverSchedule query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorDriverSchedule whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorDriverSchedule whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorDriverSchedule whereDriverId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorDriverSchedule whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorDriverSchedule whereShift($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorDriverSchedule whereTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorDriverSchedule whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorDriverSchedule whereVehicleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorDriverSchedule whereVendorId($value)
 * @mixin \Eloquent
 */
class VendorDriverSchedule extends Model {
    use HasFactory;

    protected $table = 'vendor_driver_schedules';

    protected $fillable = ['vendor_id', 'driver_id', 'vehicle_id', 'date', 'shift', 'time'];

    public function vendor() {
        return $this->belongsTo(Vendor::class);
    }

    public function driver() {
        return $this->belongsTo(VendorDriver::class);
    }

    public function vehicle() {
        return $this->belongsTo(VendorVehicle::class);
    }
}
