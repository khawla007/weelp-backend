<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $description
 * @property string $item_type
 * @property bool $featured_package
 * @property bool $private_package
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PackageAddon> $addons
 * @property-read int|null $addons_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PackageAttribute> $attributes
 * @property-read int|null $attributes_count
 * @property-read \App\Models\PackageAvailability|null $availability
 * @property-read \App\Models\PackageBasePricing|null $basePricing
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PackageCategory> $categories
 * @property-read int|null $categories_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PackageFaq> $faqs
 * @property-read int|null $faqs_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PackageInclusionExclusion> $inclusionsExclusions
 * @property-read int|null $inclusions_exclusions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PackageInformation> $information
 * @property-read int|null $information_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PackageLocation> $locations
 * @property-read int|null $locations_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PackageMediaGallery> $mediaGallery
 * @property-read int|null $media_gallery_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Order> $orders
 * @property-read int|null $orders_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PostItemTag> $postTags
 * @property-read int|null $post_tags_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Review> $reviews
 * @property-read int|null $reviews_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PackageSchedule> $schedules
 * @property-read int|null $schedules_count
 * @property-read \App\Models\PackageSeo|null $seo
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PackageTag> $tags
 * @property-read int|null $tags_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package whereFeaturedPackage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package whereItemType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package wherePrivatePackage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'item_type',
        'featured_package',
        'private_package',
    ];

    protected $casts = [
        'featured_package' => 'boolean',
        'private_package' => 'boolean',
    ];

    public function locations(): HasMany
    {

        return $this->hasMany(PackageLocation::class);
    }

    public function information(): HasMany
    {
        return $this->hasMany(PackageInformation::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(PackageSchedule::class);
    }

    public function basePricing(): HasOne
    {
        return $this->hasOne(PackageBasePricing::class, 'package_id');
    }

    public function inclusionsExclusions(): HasMany
    {
        return $this->hasMany(PackageInclusionExclusion::class);
    }

    // Category relation
    public function categories(): HasMany
    {
        return $this->hasMany(PackageCategory::class);
    }

    // Attribute relation
    public function attributes(): HasMany
    {
        return $this->hasMany(PackageAttribute::class);
    }

    // Tag relation
    public function tags(): HasMany
    {
        return $this->hasMany(PackageTag::class);
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(PackageFaq::class);
    }

    public function seo(): HasOne
    {
        return $this->hasOne(PackageSeo::class);
    }

    public function availability(): HasOne
    {
        return $this->hasOne(PackageAvailability::class);
    }

    public function mediaGallery(): HasMany
    {
        return $this->hasMany(PackageMediaGallery::class);
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
        return $this->hasMany(PackageAddon::class);
    }

    public function postTags(): MorphMany
    {
        return $this->morphMany(PostItemTag::class, 'taggable');
    }
}
