<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'code', 'slug', 'type', 'state_id', 'description', 
        'feature_image', 'featured_city'
    ];
    protected $casts = [
        'featured_destination' => 'boolean'
    ];
    // public function country(): BelongsTo
    // {
    //     return $this->belongsTo(Country::class);
    // }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function country()
    {
        return $this->hasOneThrough(Country::class, State::class, 'country_id', 'id', 'state_id', 'id');
    }

    public function region()
    {
        return $this->hasOneThrough(
            Region::class,
            RegionCountry::class, // Pivot table ka model
            'country_id', // Foreign key on region_country table
            'id', // Foreign key on regions table
            'state_id', // Local key on cities table
            'region_id' // Local key on region_country table
        );
    }

    public function mediaGallery()
    {
        return $this->hasMany(CityMediaGallery::class, 'city_id');
    }

    public function locationDetails()
    {
        return $this->hasOne(CityLocationDetail::class);
    }

    public function travelInfo()
    {
        return $this->hasOne(CityTravelInfo::class);
    }

    public function seasons()
    {
        return $this->hasMany(CitySeason::class);
    }

    public function events()
    {
        return $this->hasMany(CityEvent::class);
    }

    public function additionalInfo()
    {
        return $this->hasMany(CityAdditionalInfo::class);
    }

    public function faqs()
    {
        return $this->hasMany(CityFaq::class);
    }

    public function seo()
    {
        return $this->hasOne(CitySeo::class);
    }

    public function places() {
        return $this->hasMany(Place::class);
    }

    public function activityLocations() {
        return $this->hasMany(ActivityLocation::class, 'city_id');
    }

    public function activities() {
        return $this->hasManyThrough(Activity::class, ActivityLocation::class, 'city_id', 'id', 'id', 'activity_id');
    }

    public function itineraryLocations() {
        return $this->hasMany(ItineraryLocation::class, 'city_id');
    }

    public function itineraries() {
        return $this->hasManyThrough(Itinerary::class, ItineraryLocation::class, 'city_id', 'id', 'id', 'itinerary_id');
    }

    public function packageLocations() {
        return $this->hasMany(PackageLocation::class, 'city_id');
    }

    public function packages() {
        return $this->hasManyThrough(Package::class, PackageLocation::class, 'city_id', 'id', 'id', 'package_id');
    }
}
