<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $state_id
 * @property string $name
 * @property array<array-key, mixed> $months
 * @property string|null $weather
 * @property array<array-key, mixed>|null $activities
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\State $state
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateSeason newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateSeason newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateSeason query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateSeason whereActivities($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateSeason whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateSeason whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateSeason whereMonths($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateSeason whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateSeason whereStateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateSeason whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateSeason whereWeather($value)
 *
 * @mixin \Eloquent
 */
class StateSeason extends Model
{
    use HasFactory;

    protected $fillable = [
        'state_id',
        'name',
        'months',
        'weather',
        'activities',
    ];

    protected $casts = [
        'months' => 'array',
        'activities' => 'array',
    ];

    public function state()
    {
        return $this->belongsTo(State::class);
    }
}
