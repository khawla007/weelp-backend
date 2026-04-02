<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $country_id
 * @property numeric|null $latitude
 * @property numeric|null $longitude
 * @property string|null $capital_city
 * @property int|null $population
 * @property string|null $currency
 * @property string|null $timezone
 * @property array<array-key, mixed>|null $language
 * @property array<array-key, mixed>|null $local_cuisine
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Country $country
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryLocationDetail newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryLocationDetail newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryLocationDetail query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryLocationDetail whereCapitalCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryLocationDetail whereCountryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryLocationDetail whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryLocationDetail whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryLocationDetail whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryLocationDetail whereLanguage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryLocationDetail whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryLocationDetail whereLocalCuisine($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryLocationDetail whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryLocationDetail wherePopulation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryLocationDetail whereTimezone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryLocationDetail whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class CountryLocationDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'country_id', 'latitude', 'longitude', 'capital_city',
        'population', 'currency', 'timezone', 'language', 'local_cuisine',
    ];

    protected $casts = [
        'language' => 'array',
        'local_cuisine' => 'array',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
