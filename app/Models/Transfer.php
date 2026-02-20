<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transfer extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'slug',
        'description',
        'item_type', // Fixed value 'transfer'
        'transfer_type',
    ];

    // Relationship with TransferVendorRoute
    public function vendorRoutes()
    {
        return $this->hasOne(TransferVendorRoute::class)->with('vendor', 'route');
    }

    // Relationship with TransferPricingAvailability
    public function pricingAvailability()
    {
        return $this->hasOne(TransferPricingAvailability::class)->with('pricingTier', 'availability');
    }

    // Relationship with Media
    public function mediaGallery()
    {
        return $this->hasMany(TransferMediaGallery::class);
    }

    // Relationship with Schedule
    public function schedule()
    {
        return $this->hasOne(TransferSchedule::class);
    }

    // Relationship with SEO
    public function seo()
    {
        return $this->hasOne(TransferSeo::class);
    }

    public function itineraryTransfer() {
        return $this->hasMany(ItenraryTransferMapping::class, 'transfer_id');
    }
    
    public function itineraries() {
        return $this->hasManyThrough(Itinerary::class, ItenraryTransferMapping::class, 'transfer_id', 'id', 'id', 'itinerary_id');
    }

    public function packageTransfer() {
        return $this->hasMany(PackageTransferMapping::class, 'transfer_id');
    }
    
    public function packages() {
        return $this->hasManyThrough(Package::class, PackageTransferMapping::class, 'transfer_id', 'id', 'id', 'package_id');
    }

    public function reviews()
    {
        return $this->morphMany(Review::class, 'item', 'item_type', 'item_id');
    }
}
