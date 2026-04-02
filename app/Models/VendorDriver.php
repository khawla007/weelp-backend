<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $vendor_id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string $phone
 * @property string $license_number
 * @property string $license_expiry
 * @property string $status
 * @property int|null $assigned_vehicle_id
 * @property array<array-key, mixed>|null $languages
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\VendorVehicle|null $assignedVehicle
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\VendorDriverSchedule> $schedules
 * @property-read int|null $schedules_count
 * @property-read \App\Models\Vendor $vendor
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorDriver newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorDriver newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorDriver query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorDriver whereAssignedVehicleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorDriver whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorDriver whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorDriver whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorDriver whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorDriver whereLanguages($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorDriver whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorDriver whereLicenseExpiry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorDriver whereLicenseNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorDriver wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorDriver whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorDriver whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorDriver whereVendorId($value)
 *
 * @mixin \Eloquent
 */
class VendorDriver extends Model
{
    use HasFactory;

    protected $table = 'vendor_drivers';

    protected $fillable = ['vendor_id', 'first_name', 'last_name', 'email', 'phone', 'license_number', 'license_expiry', 'status', 'assigned_vehicle_id', 'languages'];

    protected $casts = [
        'languages' => 'array', // JSON array
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function assignedVehicle()
    {
        return $this->belongsTo(VendorVehicle::class, 'assigned_vehicle_id');
    }

    public function schedules()
    {
        return $this->hasMany(VendorDriverSchedule::class, 'driver_id');
    }
}
