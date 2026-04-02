<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $transfer_id
 * @property bool $is_vendor
 * @property int|null $vendor_id
 * @property int|null $route_id
 * @property string|null $pickup_location
 * @property string|null $dropoff_location
 * @property string|null $vehicle_type
 * @property string|null $inclusion
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\VendorRoute|null $route
 * @property-read \App\Models\Transfer $transfer
 * @property-read \App\Models\Vendor|null $vendor
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferVendorRoute newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferVendorRoute newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferVendorRoute query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferVendorRoute whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferVendorRoute whereDropoffLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferVendorRoute whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferVendorRoute whereInclusion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferVendorRoute whereIsVendor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferVendorRoute wherePickupLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferVendorRoute whereRouteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferVendorRoute whereTransferId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferVendorRoute whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferVendorRoute whereVehicleType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferVendorRoute whereVendorId($value)
 *
 * @mixin \Eloquent
 */
class TransferVendorRoute extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfer_id',
        'is_vendor',
        'vendor_id',
        'route_id',
        'pickup_location',
        'dropoff_location',
        'vehicle_type',
        'inclusion',
    ];

    protected $casts = [
        'is_vendor' => 'boolean',
    ];

    // Relationship with Transfer
    public function transfer(): BelongsTo
    {
        return $this->belongsTo(Transfer::class);
    }

    // Relationship with Vendor
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    // Relationship with Vendor Route
    public function route(): BelongsTo
    {
        return $this->belongsTo(VendorRoute::class);
    }
}
