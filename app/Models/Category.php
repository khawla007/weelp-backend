<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string $status
 * @property bool $is_featured
 * @property string $taxonomy
 * @property int|null $parent_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityCategory> $activityCategories
 * @property-read int|null $activity_categories_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Blog> $blogs
 * @property-read int|null $blogs_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Itinerary> $itineraries
 * @property-read int|null $itineraries_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ItineraryCategory> $itineraryCategories
 * @property-read int|null $itinerary_categories_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PackageCategory> $packageCategories
 * @property-read int|null $package_categories_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Package> $packages
 * @property-read int|null $packages_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereIsFeatured($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereTaxonomy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
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

    public function activityCategories()
    {
        return $this->hasMany(ActivityCategory::class, 'category_id');
    }

    public function activities()
    {
        return $this->hasManyThrough(Activity::class, ActivityCategory::class, 'category_id', 'id', 'id', 'activity_id');
    }

    public function itineraryCategories()
    {
        return $this->hasMany(ItineraryCategory::class, 'category_id');
    }

    public function itineraries()
    {
        return $this->hasManyThrough(Itinerary::class, ItineraryCategory::class, 'category_id', 'id', 'id', 'itinerary_id');
    }

    public function packageCategories()
    {
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
