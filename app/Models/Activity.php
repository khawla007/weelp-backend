<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string $item_type
 * @property string|null $short_description
 * @property bool $featured_activity
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityAddon> $addons
 * @property-read int|null $addons_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityAttribute> $attributes
 * @property-read int|null $attributes_count
 * @property-read \App\Models\ActivityAvailability|null $availability
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityCategory> $categories
 * @property-read int|null $categories_count
 * @property-read \App\Models\ActivityEarlyBirdDiscount|null $earlyBirdDiscount
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityGroupDiscount> $groupDiscounts
 * @property-read int|null $group_discounts_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Itinerary> $itineraries
 * @property-read int|null $itineraries_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ItineraryActivity> $itineraryActivity
 * @property-read int|null $itinerary_activity_count
 * @property-read \App\Models\ActivityLastMinuteDiscount|null $lastMinuteDiscount
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityLocation> $locations
 * @property-read int|null $locations_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityMediaGallery> $mediaGallery
 * @property-read int|null $media_gallery_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Order> $orders
 * @property-read int|null $orders_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PackageActivity> $packageActivity
 * @property-read int|null $package_activity_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Package> $packages
 * @property-read int|null $packages_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PostItemTag> $postTags
 * @property-read int|null $post_tags_count
 * @property-read \App\Models\ActivityPricing|null $pricing
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPromoCode> $promoCodes
 * @property-read int|null $promo_codes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Review> $reviews
 * @property-read int|null $reviews_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivitySeasonalPricing> $seasonalPricing
 * @property-read int|null $seasonal_pricing_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityTag> $tags
 * @property-read int|null $tags_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity whereFeaturedActivity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity whereItemType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity whereShortDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'description', 'item_type', 'short_description', 'featured_activity',
    ];

    protected $casts = [
        'featured_images' => 'array',
        'featured_activity' => 'boolean',
    ];

    public function categories(): HasMany
    {
        return $this->hasMany(ActivityCategory::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(ActivityLocation::class);
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(ActivityAttribute::class);
    }

    public function tags(): HasMany
    {
        return $this->hasMany(ActivityTag::class);
    }

    public function pricing(): HasOne
    {
        return $this->hasOne(ActivityPricing::class);
    }

    public function seasonalPricing(): HasMany
    {
        return $this->hasMany(ActivitySeasonalPricing::class, 'activity_id');
    }

    public function groupDiscounts(): HasMany
    {
        return $this->hasMany(ActivityGroupDiscount::class, 'activity_id');
    }

    public function earlyBirdDiscount(): HasOne
    {
        return $this->hasOne(ActivityEarlyBirdDiscount::class, 'activity_id');
    }

    public function lastMinuteDiscount(): HasOne
    {
        return $this->hasOne(ActivityLastMinuteDiscount::class, 'activity_id');
    }

    public function promoCodes(): HasMany
    {
        return $this->hasMany(ActivityPromoCode::class, 'activity_id');
    }

    public function availability(): HasOne
    {
        return $this->hasOne(ActivityAvailability::class);
    }

    public function mediaGallery(): HasMany
    {
        return $this->hasMany(ActivityMediaGallery::class);
    }

    public function itineraryActivity(): HasMany
    {
        return $this->hasMany(ItineraryActivity::class, 'activity_id');
    }

    public function itineraries(): HasManyThrough
    {
        return $this->hasManyThrough(Itinerary::class, ItineraryActivity::class, 'activity_id', 'id', 'id', 'itinerary_id');
    }

    public function packageActivity(): HasMany
    {
        return $this->hasMany(PackageActivity::class, 'activity_id');
    }

    public function packages(): HasManyThrough
    {
        return $this->hasManyThrough(Package::class, PackageActivity::class, 'activity_id', 'id', 'id', 'package_id');
    }

    // public function blogs()
    // {
    //     return $this->hasMany(Blog::class);
    // }

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

    // public function addons()
    // {
    //     return $this->belongsToMany(Addon::class, 'activity_addons', 'activity_id', 'addon_id')->withTimestamps();
    // }
    public function addons(): HasMany
    {
        return $this->hasMany(ActivityAddon::class);
    }

    public function postTags(): MorphMany
    {
        return $this->morphMany(PostItemTag::class, 'taggable');
    }
}
