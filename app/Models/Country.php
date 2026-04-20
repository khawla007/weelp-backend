<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string $slug
 * @property string $type
 * @property string|null $region
 * @property string|null $description
 * @property bool $featured_destination
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $feature_image
 * @property array $media_gallery
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CountryAdditionalInfo> $additionalInfo
 * @property-read int|null $additional_info_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CountryEvent> $events
 * @property-read int|null $events_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CountryFaq> $faqs
 * @property-read int|null $faqs_count
 * @property-read \App\Models\CountryLocationDetail|null $locationDetails
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CountryMediaGallery> $mediaGallery
 * @property-read int|null $media_gallery_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Region> $regions
 * @property-read int|null $regions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CountrySeason> $seasons
 * @property-read int|null $seasons_count
 * @property-read \App\Models\CountrySeo|null $seo
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\State> $states
 * @property-read int|null $states_count
 * @property-read \App\Models\CountryTravelInfo|null $travelInfo
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Country newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Country newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Country query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Country whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Country whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Country whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Country whereFeaturedDestination($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Country whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Country whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Country whereRegion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Country whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Country whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Country whereUpdatedAt($value)
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class Country extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'slug',
        'type',
        'description',
        'featured_destination',
    ];

    protected $casts = [
        'featured_destination' => 'boolean',
    ];

    public function regions(): BelongsToMany
    {
        // return $this->belongsToMany(Region::class, 'region_country');
        return $this->belongsToMany(Region::class, 'region_country', 'country_id', 'region_id');
    }

    // public function cities(): HasMany
    // {
    //     return $this->hasMany(City::class);
    // }

    public function mediaGallery(): HasMany
    {
        return $this->hasMany(CountryMediaGallery::class, 'country_id');
    }

    public function locationDetails(): HasOne
    {
        return $this->hasOne(CountryLocationDetail::class);
    }

    public function travelInfo(): HasOne
    {
        return $this->hasOne(CountryTravelInfo::class);
    }

    public function seasons(): HasMany
    {
        return $this->hasMany(CountrySeason::class, 'country_id', 'id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(CountryEvent::class, 'country_id', 'id');
    }

    public function additionalInfo(): HasMany
    {
        return $this->hasMany(CountryAdditionalInfo::class);
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(CountryFaq::class);
    }

    public function seo(): HasOne
    {
        return $this->hasOne(CountrySeo::class);
    }

    public function states(): HasMany
    {
        return $this->hasMany(State::class);
    }
}
