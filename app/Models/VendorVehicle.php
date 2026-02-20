<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
