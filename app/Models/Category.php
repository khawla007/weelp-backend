<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Category extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'taxonomy', 'post_type', 'parent_id', 'status', 'is_featured'];

    protected $casts = [
        'is_featured' => 'boolean',
    ];

    // Automatically generate slug when creating or updating
    protected static function boot()
    {
        parent::boot();

        // static::saving(function ($category) {
        //     $category->slug = Str::slug(str_replace(' ', '_', strtolower($category->name)), '_');
        // });
    }

    public function activityCategories() {
        return $this->hasMany(ActivityCategory::class, 'category_id');
    }
    
    public function activities() {
        return $this->hasManyThrough(Activity::class, ActivityCategory::class, 'category_id', 'id', 'id', 'activity_id');
    }

    public function itineraryCategories() {
        return $this->hasMany(ItineraryCategory::class, 'category_id');
    }
    
    public function itineraries()
    {
        return $this->hasManyThrough(Itinerary::class, ItineraryCategory::class, 'category_id', 'id', 'id', 'itinerary_id');
    }

    public function packageCategories() {
        return $this->hasMany(PackageCategory::class, 'category_id');
    }
    
    public function packages()
    {
        return $this->hasManyThrough(Package::class, PackageCategory::class, 'category_id', 'id', 'id', 'package_id');
    }

    // public function blogs()
    // {
    //     return $this->hasMany(Blog::class);
    // }
    public function blogs()
    {
        return $this->belongsToMany(Blog::class, 'blog_category');
    }
}
