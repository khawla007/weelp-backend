<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string $slug
 * @property string $type
 * @property int $city_id
 * @property string|null $description
 * @property bool $featured_destination
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $feature_image
 * @property array $media_gallery
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PlaceAdditionalInfo> $additionalInfo
 * @property-read int|null $additional_info_count
 * @property-read \App\Models\City $city
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PlaceEvent> $events
 * @property-read int|null $events_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PlaceFaq> $faqs
 * @property-read int|null $faqs_count
 * @property-read \App\Models\PlaceLocationDetail|null $locationDetails
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PlaceMediaGallery> $mediaGallery
 * @property-read int|null $media_gallery_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PlaceSeason> $seasons
 * @property-read int|null $seasons_count
 * @property-read \App\Models\PlaceSeo|null $seo
 * @property-read \App\Models\PlaceTravelInfo|null $travelInfo
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Place newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Place newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Place query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Place whereCityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Place whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Place whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Place whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Place whereFeaturedDestination($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Place whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Place whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Place whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Place whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Place whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Place extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'slug',
        'type',
        'city_id',
        'description',
        'featured_destination',
    ];

    protected $casts = [
        'featured_destination' => 'boolean',
    ];

    public function mediaGallery(): HasMany
    {
        return $this->hasMany(PlaceMediaGallery::class, 'place_id');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function locationDetails(): HasOne
    {
        return $this->hasOne(PlaceLocationDetail::class);
    }

    public function travelInfo(): HasOne
    {
        return $this->hasOne(PlaceTravelInfo::class);
    }

    public function seasons(): HasMany
    {
        return $this->hasMany(PlaceSeason::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(PlaceEvent::class);
    }

    public function additionalInfo(): HasMany
    {
        return $this->hasMany(PlaceAdditionalInfo::class);
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(PlaceFaq::class);
    }

    public function seo(): HasOne
    {
        return $this->hasOne(PlaceSeo::class);
    }
}
