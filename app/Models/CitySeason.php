<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $city_id
 * @property string $name
 * @property array<array-key, mixed>|null $months
 * @property string|null $weather
 * @property array<array-key, mixed>|null $activities
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\City $city
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CitySeason newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CitySeason newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CitySeason query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CitySeason whereActivities($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CitySeason whereCityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CitySeason whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CitySeason whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CitySeason whereMonths($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CitySeason whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CitySeason whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CitySeason whereWeather($value)
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class CitySeason extends Model
{
    use HasFactory;

    protected $fillable = [
        'city_id', 'name', 'months', 'weather', 'activities',
    ];

    protected $casts = [
        'months' => 'array',
        'activities' => 'array',
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
