<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $vendor_id
 * @property string $name
 * @property string|null $description
 * @property string $start_point
 * @property string $end_point
 * @property numeric $base_price
 * @property numeric $price_per_km
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TransferVendorRoute> $transferRoutes
 * @property-read int|null $transfer_routes_count
 * @property-read \App\Models\Vendor $vendor
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorRoute newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorRoute newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorRoute query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorRoute whereBasePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorRoute whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorRoute whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorRoute whereEndPoint($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorRoute whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorRoute whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorRoute wherePricePerKm($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorRoute whereStartPoint($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorRoute whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorRoute whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorRoute whereVendorId($value)
 *
 * @mixin \Eloquent
 */
class VendorRoute extends Model
{
    use HasFactory;

    protected $table = 'vendor_routes';

    protected $fillable = ['vendor_id', 'name', 'description', 'start_point', 'end_point', 'base_price', 'price_per_km', 'status'];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function transferRoutes()
    {
        return $this->hasMany(TransferVendorRoute::class, 'route_id');
    }
}
