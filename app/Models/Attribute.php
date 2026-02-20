<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Attribute extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'description',
        'values',
        'default_value',
        'taxonomy',
        'post_type',
    ];

    protected $casts = [
        'values' => 'array', // Automatically handles JSON conversion
    ];

    /**
     * Automatically set the slug and taxonomy before saving.
     */
    public static function boot()
    {
        parent::boot();

        static::creating(function ($attribute) {
            $slug = Str::slug($attribute->name, '-');
            $attribute->slug = $slug;
            $attribute->taxonomy = $slug;
        });

        static::updating(function ($attribute) {
            $slug = Str::slug($attribute->name, '-');
            $attribute->slug = $slug;
            $attribute->taxonomy = $slug;
        });
    }

    public function activityAttributes() {
        return $this->hasMany(ActivityAttributeValue::class, 'attribute_id');
    }
    
    public function activities() {
        return $this->hasManyThrough(Activity::class, ActivityAttributeValue::class, 'attribute_id', 'id', 'id', 'activity_id');
    }

    public function itinerariesAttributes() {
        return $this->hasMany(ItineraryAttributeValue::class, 'attribute_id');
    }

    public function itineraries()
    {
        return $this->hasManyThrough(Itinerary::class, ItineraryAttributeValue::class, 'attribute_id', 'id', 'id', 'activity_id');
    }
}
