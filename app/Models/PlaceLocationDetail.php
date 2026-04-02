<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $place_id
 * @property numeric $latitude
 * @property numeric $longitude
 * @property int|null $population
 * @property string|null $currency
 * @property string $timezone
 * @property array<array-key, mixed> $language
 * @property array<array-key, mixed>|null $local_cuisine
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Place $place
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceLocationDetail newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceLocationDetail newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceLocationDetail query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceLocationDetail whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceLocationDetail whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceLocationDetail whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceLocationDetail whereLanguage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceLocationDetail whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceLocationDetail whereLocalCuisine($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceLocationDetail whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceLocationDetail wherePlaceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceLocationDetail wherePopulation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceLocationDetail whereTimezone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceLocationDetail whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class PlaceLocationDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'place_id', 'latitude', 'longitude', 'population', 'currency',
        'timezone', 'language', 'local_cuisine',
    ];

    protected $casts = [
        'language' => 'array',
        'local_cuisine' => 'array',
    ];

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }
}
