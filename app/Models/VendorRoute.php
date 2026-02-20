<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorRoute extends Model {
    use HasFactory;

    protected $table = 'vendor_routes';

    protected $fillable = ['vendor_id', 'name', 'description', 'start_point', 'end_point', 'base_price', 'price_per_km', 'status'];

    public function vendor() {
        return $this->belongsTo(Vendor::class);
    }

    public function transferRoutes()
    {
        return $this->hasMany(TransferVendorRoute::class, 'route_id');
    }
}
