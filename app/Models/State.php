<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'code', 'slug', 'type', 'country_id', 'description', 'feature_image', 'featured_destination'
    ];

    protected $casts = [
        'featured_destination' => 'boolean'
    ];
    
    public function country() {
        return $this->belongsTo(Country::class);
    }

    public function mediaGallery()
    {
        return $this->hasMany(StateMediaGallery::class, 'state_id');
    }
    
    public function locationDetails()
    {
        return $this->hasOne(StateLocationDetail::class);
    }

    public function travelInfo()
    {
        return $this->hasOne(StateTravelInfo::class);
    }

    public function seasons()
    {
        return $this->hasMany(StateSeason::class);
    }

    public function events()
    {
        return $this->hasMany(StateEvent::class);
    }

    public function additionalInfo()
    {
        return $this->hasMany(StateAdditionalInfo::class);
    }

    public function faqs()
    {
        return $this->hasMany(StateFaq::class);
    }

    public function seo()
    {
        return $this->hasOne(StateSeo::class);
    }

    public function cities() {
        return $this->hasMany(City::class);
    }
}
