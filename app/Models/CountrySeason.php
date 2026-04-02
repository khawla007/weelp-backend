<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $country_id
 * @property string $name
 * @property array<array-key, mixed>|null $months
 * @property string $weather
 * @property array<array-key, mixed>|null $activities
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Country $country
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountrySeason newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountrySeason newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountrySeason query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountrySeason whereActivities($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountrySeason whereCountryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountrySeason whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountrySeason whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountrySeason whereMonths($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountrySeason whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountrySeason whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountrySeason whereWeather($value)
 *
 * @mixin \Eloquent
 */
class CountrySeason extends Model
{
    use HasFactory;

    protected $fillable = [
        'country_id',
        'name',
        'months',
        'weather',
        'activities',
    ];

    protected $casts = [
        'months' => 'array',
        'activities' => 'array',
    ];

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id', 'id');
    }
}
