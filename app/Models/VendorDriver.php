<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorDriver extends Model {
    use HasFactory;

    protected $table = 'vendor_drivers';

    protected $fillable = ['vendor_id', 'first_name', 'last_name', 'email', 'phone', 'license_number', 'license_expiry', 'status', 'assigned_vehicle_id', 'languages'];

    protected $casts = [
        'languages' => 'array', // JSON array
    ];

    public function vendor() {
        return $this->belongsTo(Vendor::class);
    }

    public function assignedVehicle() {
        return $this->belongsTo(VendorVehicle::class, 'assigned_vehicle_id');
    }

    public function schedules() {
        return $this->hasMany(VendorDriverSchedule::class, 'driver_id');
    }
}
