<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $place_id
 * @property string $name
 * @property array<array-key, mixed> $months
 * @property string $weather
 * @property array<array-key, mixed> $activities
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Place $place
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceSeason newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceSeason newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceSeason query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceSeason whereActivities($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceSeason whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceSeason whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceSeason whereMonths($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceSeason whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceSeason wherePlaceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceSeason whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceSeason whereWeather($value)
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class PlaceSeason extends Model
{
    use HasFactory;

    protected $fillable = [
        'place_id',
        'name',
        'months',
        'weather',
        'activities',
    ];

    protected $casts = [
        'months' => 'array',
        'activities' => 'array',
    ];

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }
}
