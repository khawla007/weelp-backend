<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    protected $table = 'media';

    protected $fillable = ['name', 'alt_text', 'url'];

    public function userAvatar()
    {
        return $this->hasOne(User::class, 'avatar');
    }

    public function countryMedia()
    {
        return $this->hasMany(CountryMediaGallery::class, 'media_id');
    }

    public function stateMedia()
    {
        return $this->hasMany(StateMediaGallery::class, 'media_id');
    }

    public function cityMedia()
    {
        return $this->hasMany(CityMediaGallery::class, 'media_id');
    }

    public function placeMedia()
    {
        return $this->hasMany(PlaceMediaGallery::class, 'media_id');
    }
    
    // public function blogs()
    // {
    //     return $this->hasMany(Blog::class, 'featured_image');
    // }
    public function blogs()
    {
        return $this->belongsToMany(Blog::class, 'blog_media');
    }

    public function itineraryMedia()
    {
        return $this->hasMany(ItineraryMediaGallery::class, 'media_id');
    }

    public function packageMedia()
    {
        return $this->hasMany(PackageMediaGallery::class, 'media_id');
    }

    public function activityMedia()
    {
        return $this->hasMany(ActivityMediaGallery::class, 'media_id');
    }

    public function transferMedia()
    {
        return $this->hasMany(TransferyMediaGallery::class, 'media_id');
    }

    public function reviews()
    {
        return $this->belongsToMany(Review::class, 'review_media', 'media_id', 'review_id');
    }
    
}
