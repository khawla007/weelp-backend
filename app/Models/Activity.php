<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model {
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'description', 'item_type', 'short_description', 'featured_activity'
    ];

    protected $casts = [
        'featured_images' => 'array',
        'featured_activity' => 'boolean'
    ];

    public function categories() {
        return $this->hasMany(ActivityCategory::class);
    }

    public function locations() {
        return $this->hasMany(ActivityLocation::class);
    }

    public function attributes() {
        return $this->hasMany(ActivityAttribute::class);
    }

    public function tags()
    {
        return $this->hasMany(ActivityTag::class);
    }

    public function pricing() {
        return $this->hasOne(ActivityPricing::class);
    }

    public function seasonalPricing() {
        return $this->hasMany(ActivitySeasonalPricing::class, 'activity_id');
    }

    public function groupDiscounts() {
        return $this->hasMany(ActivityGroupDiscount::class, 'activity_id');
    }

    public function earlyBirdDiscount() {
        return $this->hasOne(ActivityEarlyBirdDiscount::class, 'activity_id');
    }

    public function lastMinuteDiscount() {
        return $this->hasOne(ActivityLastMinuteDiscount::class, 'activity_id');
    }

    public function promoCodes() {
        return $this->hasMany(ActivityPromoCode::class, 'activity_id');
    }
    
    public function availability()
    {
        return $this->hasOne(ActivityAvailability::class);
    }

    public function mediaGallery()
    {
        return $this->hasMany(ActivityMediaGallery::class);
    }

    public function itineraryActivity() {
        return $this->hasMany(ItenraryActivityMapping::class, 'activity_id');
    }
    
    public function itineraries() {
        return $this->hasManyThrough(Itinerary::class, ItenraryActivityMapping::class, 'activity_id', 'id', 'id', 'itinerary_id');
    }

    public function packageActivity() {
        return $this->hasMany(PackageActivityMapping::class, 'activity_id');
    }
    
    public function packages() {
        return $this->hasManyThrough(Package::class, PackageActivityMapping::class, 'activity_id', 'id', 'id', 'package_id');
    }

    // public function blogs()
    // {
    //     return $this->hasMany(Blog::class);
    // }

    public function orders()
    {
        return $this->morphMany(Order::class, 'orderable');
    }

    public function reviews()
    {
        return $this->morphMany(Review::class, 'item', 'item_type', 'item_id');
    }

    // public function addons()
    // {
    //     return $this->belongsToMany(Addon::class, 'activity_addons', 'activity_id', 'addon_id')->withTimestamps();
    // }
    public function addons()
    {
        return $this->hasMany(ActivityAddon::class);
    }
}
