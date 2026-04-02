<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

/**
 * @property int $id
 * @property string $name
 * @property string|null $code
 * @property string $slug
 * @property string $type
 * @property int $state_id
 * @property string|null $description
 * @property bool $featured_destination
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $feature_image
 * @property array $media_gallery
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityLocation> $activityLocations
 * @property-read int|null $activity_locations_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CityAdditionalInfo> $additionalInfo
 * @property-read int|null $additional_info_count
 * @property-read \App\Models\Country|null $country
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CityEvent> $events
 * @property-read int|null $events_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CityFaq> $faqs
 * @property-read int|null $faqs_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Itinerary> $itineraries
 * @property-read int|null $itineraries_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ItineraryLocation> $itineraryLocations
 * @property-read int|null $itinerary_locations_count
 * @property-read \App\Models\CityLocationDetail|null $locationDetails
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CityMediaGallery> $mediaGallery
 * @property-read int|null $media_gallery_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PackageLocation> $packageLocations
 * @property-read int|null $package_locations_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Package> $packages
 * @property-read int|null $packages_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Place> $places
 * @property-read int|null $places_count
 * @property-read \App\Models\Region|null $region
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CitySeason> $seasons
 * @property-read int|null $seasons_count
 * @property-read \App\Models\CitySeo|null $seo
 * @property-read \App\Models\State $state
 * @property-read \App\Models\CityTravelInfo|null $travelInfo
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|City newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|City newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|City query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|City whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|City whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|City whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|City whereFeaturedDestination($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|City whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|City whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|City whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|City whereStateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|City whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|City whereUpdatedAt($value)
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class City extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'code', 'slug', 'type', 'state_id', 'description',
        'featured_city',
    ];

    protected $casts = [
        'featured_destination' => 'boolean',
    ];
    // public function country(): BelongsTo
    // {
    //     return $this->belongsTo(Country::class);
    // }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function country(): HasOneThrough
    {
        return $this->hasOneThrough(Country::class, State::class, 'country_id', 'id', 'state_id', 'id');
    }

    public function region(): HasOneThrough
    {
        return $this->hasOneThrough(
            Region::class,
            RegionCountry::class, // Pivot table ka model
            'country_id', // Foreign key on region_country table
            'id', // Foreign key on regions table
            'state_id', // Local key on cities table
            'region_id' // Local key on region_country table
        );
    }

    public function mediaGallery(): HasMany
    {
        return $this->hasMany(CityMediaGallery::class, 'city_id');
    }

    public function locationDetails(): HasOne
    {
        return $this->hasOne(CityLocationDetail::class);
    }

    public function travelInfo(): HasOne
    {
        return $this->hasOne(CityTravelInfo::class);
    }

    public function seasons(): HasMany
    {
        return $this->hasMany(CitySeason::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(CityEvent::class);
    }

    public function additionalInfo(): HasMany
    {
        return $this->hasMany(CityAdditionalInfo::class);
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(CityFaq::class);
    }

    public function seo(): HasOne
    {
        return $this->hasOne(CitySeo::class);
    }

    public function places(): HasMany
    {
        return $this->hasMany(Place::class);
    }

    public function activityLocations(): HasMany
    {
        return $this->hasMany(ActivityLocation::class, 'city_id');
    }

    public function activities(): HasManyThrough
    {
        return $this->hasManyThrough(Activity::class, ActivityLocation::class, 'city_id', 'id', 'id', 'activity_id');
    }

    public function itineraryLocations(): HasMany
    {
        return $this->hasMany(ItineraryLocation::class, 'city_id');
    }

    public function itineraries(): HasManyThrough
    {
        return $this->hasManyThrough(Itinerary::class, ItineraryLocation::class, 'city_id', 'id', 'id', 'itinerary_id');
    }

    public function packageLocations(): HasMany
    {
        return $this->hasMany(PackageLocation::class, 'city_id');
    }

    public function packages(): HasManyThrough
    {
        return $this->hasManyThrough(Package::class, PackageLocation::class, 'city_id', 'id', 'id', 'package_id');
    }
}
