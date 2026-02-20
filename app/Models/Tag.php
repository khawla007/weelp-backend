<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Tag extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'taxonomy', 'post_type'];

    // Automatically generate slug when creating or updating
    protected static function boot()
    {
        parent::boot();

        // static::saving(function ($tag) {
        //     $tag->slug = Str::slug(str_replace(' ', '_', strtolower($tag->name)), '_');
        // });
    }

    public function itineraries()
    {
        return $this->belongsToMany(Itinerary::class, 'itinerary_tags');
    }

    // public function blogs()
    // {
    //     return $this->hasMany(Blog::class);
    // }
    public function blogs()
    {
        return $this->belongsToMany(Blog::class, 'blog_tag');
    }
}
