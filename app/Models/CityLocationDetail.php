<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $city_id
 * @property numeric $latitude
 * @property numeric $longitude
 * @property int|null $population
 * @property string|null $currency
 * @property string $timezone
 * @property array<array-key, mixed>|null $language
 * @property array<array-key, mixed>|null $local_cuisine
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\City $city
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityLocationDetail newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityLocationDetail newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityLocationDetail query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityLocationDetail whereCityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityLocationDetail whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityLocationDetail whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityLocationDetail whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityLocationDetail whereLanguage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityLocationDetail whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityLocationDetail whereLocalCuisine($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityLocationDetail whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityLocationDetail wherePopulation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityLocationDetail whereTimezone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityLocationDetail whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class CityLocationDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'city_id', 'latitude', 'longitude', 'population', 'currency',
        'timezone', 'language', 'local_cuisine',
    ];

    protected $casts = [
        'language' => 'array',
        'local_cuisine' => 'array',
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
