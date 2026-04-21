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

    /**
     * Static cache for resolved zone prices, keyed by "from_zone_id:to_zone_id".
     * Cleared at the start of each request cycle.
     */
    private static array $zonePriceCache = [];

    /**
     * Per-instance memoised resolved zone price.
     */
    private ?TransferZonePrice $resolvedZonePrice = null;

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

    /**
     * Resolve the zone pricing for this transfer's route, with per-request caching.
     * Returns null if route is missing or zones are not set.
     *
     * Per-instance memoisation prevents multiple queries in the same request
     * when the same transfer is accessed multiple times. Static cache allows
     * multiple transfers pointing to the same zone pair to share the lookup.
     */
    public function resolvedZonePrice(): ?TransferZonePrice
    {
        // Per-instance memoisation
        if ($this->resolvedZonePrice !== null) {
            return $this->resolvedZonePrice;
        }

        // Early exit if route is missing or zones are not set
        $route = $this->route;
        if (! $route || ! $route->from_zone_id || ! $route->to_zone_id) {
            return $this->resolvedZonePrice = null;
        }

        // Static cache key
        $cacheKey = "{$route->from_zone_id}:{$route->to_zone_id}";

        // Check static cache first
        if (isset(self::$zonePriceCache[$cacheKey])) {
            return $this->resolvedZonePrice = self::$zonePriceCache[$cacheKey];
        }

        // Query if not cached
        $resolved = TransferZonePrice::query()
            ->where('from_zone_id', $route->from_zone_id)
            ->where('to_zone_id', $route->to_zone_id)
            ->first();

        // Store in static cache (even if null)
        self::$zonePriceCache[$cacheKey] = $resolved;

        return $this->resolvedZonePrice = $resolved;
    }

    /**
     * Compute the total route price: zone base_price + non-vendor transfer_price.
     * Returns float rounded to 2 decimals.
     */
    public function computeRoutePrice(): float
    {
        $zonePrice = $this->resolvedZonePrice();
        $zoneBasePrice = $zonePrice ? (float) $zonePrice->base_price : 0.0;

        $pricingAvailability = $this->pricingAvailability;
        $nonVendorPricing = ($pricingAvailability && ! $pricingAvailability->is_vendor)
            ? $pricingAvailability
            : null;
        $transferPrice = $nonVendorPricing ? (float) $nonVendorPricing->transfer_price : 0.0;

        return round($zoneBasePrice + $transferPrice, 2);
    }

    /**
     * Get the currency for this route: zone currency takes precedence,
     * then non-vendor pricing availability currency, else null.
     */
    public function routeCurrency(): ?string
    {
        $zonePrice = $this->resolvedZonePrice();
        if ($zonePrice) {
            return $zonePrice->currency;
        }

        $pricingAvailability = $this->pricingAvailability;
        $nonVendorPricing = ($pricingAvailability && ! $pricingAvailability->is_vendor)
            ? $pricingAvailability
            : null;

        return $nonVendorPricing?->currency;
    }

    /**
     * Clear the per-request static zone price cache.
     * Call this at the start of each request cycle to prevent stale data.
     */
    public static function clearZonePriceCache(): void
    {
        self::$zonePriceCache = [];
    }
}
