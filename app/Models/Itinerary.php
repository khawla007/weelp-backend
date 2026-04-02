<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string $item_type
 * @property bool $featured_itinerary
 * @property bool $private_itinerary
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ItineraryAddon> $addons
 * @property-read int|null $addons_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ItineraryAttribute> $attributes
 * @property-read int|null $attributes_count
 * @property-read \App\Models\ItineraryAvailability|null $availability
 * @property-read \App\Models\ItineraryBasePricing|null $basePricing
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ItineraryCategory> $categories
 * @property-read int|null $categories_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ItineraryInclusionExclusion> $inclusionsExclusions
 * @property-read int|null $inclusions_exclusions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ItineraryLocation> $locations
 * @property-read int|null $locations_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ItineraryMediaGallery> $mediaGallery
 * @property-read int|null $media_gallery_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Order> $orders
 * @property-read int|null $orders_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PostItemTag> $postTags
 * @property-read int|null $post_tags_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Review> $reviews
 * @property-read int|null $reviews_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ItinerarySchedule> $schedules
 * @property-read int|null $schedules_count
 * @property-read \App\Models\ItinerarySeo|null $seo
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ItineraryTag> $tags
 * @property-read int|null $tags_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Itinerary newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Itinerary newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Itinerary query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Itinerary whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Itinerary whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Itinerary whereFeaturedItinerary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Itinerary whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Itinerary whereItemType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Itinerary whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Itinerary wherePrivateItinerary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Itinerary whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Itinerary whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Itinerary extends Model
{
    protected $table = 'itineraries';

    protected $fillable = [
        'name', 'slug', 'description', 'featured_itinerary', 'private_itinerary',
    ];

    protected $casts = [
        'featured_itinerary' => 'boolean',
        'private_itinerary' => 'boolean',
    ];

    public function locations(): HasMany
    {

        return $this->hasMany(ItineraryLocation::class);
    }

    // Schedule relation
    public function schedules(): HasMany
    {
        return $this->hasMany(ItinerarySchedule::class);
    }

    // Base pricing relation
    public function basePricing(): HasOne
    {
        return $this->hasOne(ItineraryBasePricing::class, 'itinerary_id');
    }

    // Inclusion/Exclusion relation
    public function inclusionsExclusions(): HasMany
    {
        return $this->hasMany(ItineraryInclusionExclusion::class);
    }

    // Media Gallery relation
    public function mediaGallery(): HasMany
    {
        return $this->hasMany(ItineraryMediaGallery::class);
    }

    // SEO relation
    public function seo(): HasOne
    {
        return $this->hasOne(ItinerarySeo::class);
    }

    // Category relation
    public function categories(): HasMany
    {
        return $this->hasMany(ItineraryCategory::class);
    }

    // Attribute relation
    public function attributes(): HasMany
    {
        return $this->hasMany(ItineraryAttribute::class);
    }

    // Tag relation
    public function tags(): HasMany
    {
        return $this->hasMany(ItineraryTag::class);
    }

    public function availability(): HasOne
    {
        return $this->hasOne(ItineraryAvailability::class);
    }

    public function orders(): MorphMany
    {
        return $this->morphMany(Order::class, 'orderable');
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'item', 'item_type', 'item_id');
    }

    public function getItemTypeAttribute($value)
    {
        return $value ?? strtolower(class_basename($this));
    }

    public function addons(): HasMany
    {
        return $this->hasMany(ItineraryAddon::class);
    }

    public function postTags(): MorphMany
    {
        return $this->morphMany(PostItemTag::class, 'taggable');
    }
}
