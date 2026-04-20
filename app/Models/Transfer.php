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
 * @property string $description
 * @property string $item_type
 * @property string $transfer_type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $feature_image
 * @property array $media_gallery
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TransferAddon> $addons
 * @property-read int|null $addons_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Itinerary> $itineraries
 * @property-read int|null $itineraries_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ItineraryTransferMapping> $itineraryTransfer
 * @property-read int|null $itinerary_transfer_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TransferMediaGallery> $mediaGallery
 * @property-read int|null $media_gallery_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PackageTransferMapping> $packageTransfer
 * @property-read int|null $package_transfer_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Package> $packages
 * @property-read int|null $packages_count
 * @property-read \App\Models\TransferPricingAvailability|null $pricingAvailability
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Review> $reviews
 * @property-read int|null $reviews_count
 * @property-read \App\Models\TransferSchedule|null $schedule
 * @property-read \App\Models\TransferSeo|null $seo
 * @property-read \App\Models\TransferVendorRoute|null $vendorRoutes
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transfer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transfer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transfer query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transfer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transfer whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transfer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transfer whereItemType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transfer whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transfer whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transfer whereTransferType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transfer whereUpdatedAt($value)
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class Transfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'item_type', // Fixed value 'transfer'
        'transfer_type',
        'transfer_route_id',
    ];

    public function route()
    {
        return $this->belongsTo(TransferRoute::class, 'transfer_route_id');
    }

    // Relationship with TransferVendorRoute
    public function vendorRoutes(): HasOne
    {
        return $this->hasOne(TransferVendorRoute::class)->with('vendor', 'route');
    }

    // Relationship with TransferPricingAvailability
    public function pricingAvailability(): HasOne
    {
        return $this->hasOne(TransferPricingAvailability::class)->with('pricingTier', 'availability');
    }

    // Relationship with Media
    public function mediaGallery(): HasMany
    {
        return $this->hasMany(TransferMediaGallery::class);
    }

    // Relationship with Schedule
    public function schedule(): HasOne
    {
        return $this->hasOne(TransferSchedule::class);
    }

    // Relationship with SEO
    public function seo(): HasOne
    {
        return $this->hasOne(TransferSeo::class);
    }

    public function itineraryTransfer(): HasMany
    {
        return $this->hasMany(ItineraryTransferMapping::class, 'transfer_id');
    }

    public function itineraries(): HasManyThrough
    {
        return $this->hasManyThrough(Itinerary::class, ItineraryTransferMapping::class, 'transfer_id', 'id', 'id', 'itinerary_id');
    }

    public function packageTransfer(): HasMany
    {
        return $this->hasMany(PackageTransferMapping::class, 'transfer_id');
    }

    public function packages(): HasManyThrough
    {
        return $this->hasManyThrough(Package::class, PackageTransferMapping::class, 'transfer_id', 'id', 'id', 'package_id');
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'item', 'item_type', 'item_id');
    }

    public function addons(): HasMany
    {
        return $this->hasMany(TransferAddon::class);
    }

    public function getItemTypeAttribute($value)
    {
        return $value ?? strtolower(class_basename($this));
    }
}
