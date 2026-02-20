<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'is_vendor' => 'boolean'
    ];

    // Relationship with Transfer
    public function transfer()
    {
        return $this->belongsTo(Transfer::class);
    }

    // Relationship with Vendor
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    // Relationship with Vendor Route
    public function route()
    {
        return $this->belongsTo(VendorRoute::class);
    }
}