<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Place extends Model {
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'slug',
        'type', 
        'city_id',
        'description',
        'feature_image',
        'featured_destination',
    ];

    protected $casts = [
        'featured_destination' => 'boolean'
    ];

    public function mediaGallery()
    {
        return $this->hasMany(PlaceMediaGallery::class, 'place_id');
    }
    
    public function locationDetails() {
        return $this->hasOne(PlaceLocationDetail::class);
    }

    public function travelInfo() {
        return $this->hasOne(PlaceTravelInfo::class);
    }

    public function seasons() {
        return $this->hasMany(PlaceSeason::class);
    }

    public function events() {
        return $this->hasMany(PlaceEvent::class);
    }

    public function additionalInfo() {
        return $this->hasMany(PlaceAdditionalInfo::class);
    }

    public function faqs() {
        return $this->hasMany(PlaceFaq::class);
    }

    public function seo() {
        return $this->hasOne(PlaceSeo::class);
    }
}
