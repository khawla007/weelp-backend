<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Itinerary extends Model
{
    protected $table = 'itineraries';
    protected $fillable = [
        'name', 'slug', 'description', 'featured_itinerary', 'private_itinerary'
    ];

    protected $casts = [
        'featured_itinerary' => 'boolean',
        'private_itinerary' => 'boolean'
    ];

    public function locations() {

        return $this->hasMany(ItineraryLocation::class);
    }

    // Schedule relation
    public function schedules()
    {
        return $this->hasMany(ItinerarySchedule::class);
    }

    // Base pricing relation
    public function basePricing()
    {
        return $this->hasOne(ItineraryBasePricing::class, 'itinerary_id');
    }

    // Inclusion/Exclusion relation
    public function inclusionsExclusions()
    {
        return $this->hasMany(ItineraryInclusionExclusion::class);
    }

    // Media Gallery relation
    public function mediaGallery()
    {
        return $this->hasMany(ItineraryMediaGallery::class);
    }

    // SEO relation
    public function seo()
    {
        return $this->hasOne(ItinerarySeo::class);
    }

    // Category relation
    public function categories() {
        return $this->hasMany(ItineraryCategory::class);
    }

    // Attribute relation
    public function attributes()
    {
        return $this->hasMany(ItineraryAttribute::class);
    }

    // Tag relation
    public function tags()
    {
        return $this->hasMany(ItineraryTag::class);
    }

    public function availability()
    {
        return $this->hasOne(ItineraryAvailability::class);
    }
    public function orders()
    {
        return $this->morphMany(Order::class, 'orderable');
    }

    public function reviews()
    {
        return $this->morphMany(Review::class, 'item', 'item_type', 'item_id');
    }

    public function addons()
    {
        return $this->hasMany(ItineraryAddon::class);
    }
}
