<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'slug',
        'type', 
        'description',
        'feature_image',
        'featured_destination'
    ];

    protected $casts = [
        'featured_destination' => 'boolean'
    ];

    public function regions()
    {
        // return $this->belongsToMany(Region::class, 'region_country');
        return $this->belongsToMany(Region::class, 'region_country', 'country_id', 'region_id');
    }
    
    // public function cities(): HasMany
    // {
    //     return $this->hasMany(City::class);
    // }

    public function mediaGallery()
    {
        return $this->hasMany(CountryMediaGallery::class, 'country_id');
    }
    
    public function locationDetails() {
        return $this->hasOne(CountryLocationDetail::class);
    }

    public function travelInfo() {
        return $this->hasOne(CountryTravelInfo::class);
    }

    public function seasons()
    {
        return $this->hasMany(CountrySeason::class, 'country_id', 'id');
    }
    
    public function events() {
        return $this->hasMany(CountryEvent::class, 'country_id', 'id');
    }

    public function additionalInfo() {
        return $this->hasMany(CountryAdditionalInfo::class);
    }
    
    public function faqs() {
        return $this->hasMany(CountryFaq::class);
    }
    
    public function seo() {
        return $this->hasOne(CountrySeo::class);
    }

    public function states() {
        return $this->hasMany(State::class);
    }
}
