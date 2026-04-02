<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string|null $code
 * @property string $slug
 * @property string $type
 * @property int $country_id
 * @property string|null $description
 * @property bool $featured_destination
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\StateAdditionalInfo> $additionalInfo
 * @property-read int|null $additional_info_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\City> $cities
 * @property-read int|null $cities_count
 * @property-read \App\Models\Country $country
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\StateEvent> $events
 * @property-read int|null $events_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\StateFaq> $faqs
 * @property-read int|null $faqs_count
 * @property-read \App\Models\StateLocationDetail|null $locationDetails
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\StateMediaGallery> $mediaGallery
 * @property-read int|null $media_gallery_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\StateSeason> $seasons
 * @property-read int|null $seasons_count
 * @property-read \App\Models\StateSeo|null $seo
 * @property-read \App\Models\StateTravelInfo|null $travelInfo
 * @method static \Illuminate\Database\Eloquent\Builder<static>|State newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|State newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|State query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|State whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|State whereCountryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|State whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|State whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|State whereFeaturedDestination($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|State whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|State whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|State whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|State whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|State whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class State extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'code', 'slug', 'type', 'country_id', 'description', 'featured_destination'
    ];

    protected $casts = [
        'featured_destination' => 'boolean'
    ];
    
    public function country() {
        return $this->belongsTo(Country::class);
    }

    public function mediaGallery()
    {
        return $this->hasMany(StateMediaGallery::class, 'state_id');
    }
    
    public function locationDetails()
    {
        return $this->hasOne(StateLocationDetail::class);
    }

    public function travelInfo()
    {
        return $this->hasOne(StateTravelInfo::class);
    }

    public function seasons()
    {
        return $this->hasMany(StateSeason::class);
    }

    public function events()
    {
        return $this->hasMany(StateEvent::class);
    }

    public function additionalInfo()
    {
        return $this->hasMany(StateAdditionalInfo::class);
    }

    public function faqs()
    {
        return $this->hasMany(StateFaq::class);
    }

    public function seo()
    {
        return $this->hasOne(StateSeo::class);
    }

    public function cities() {
        return $this->hasMany(City::class);
    }
}
