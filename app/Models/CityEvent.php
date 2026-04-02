<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $city_id
 * @property string $name
 * @property array<array-key, mixed> $type
 * @property \Illuminate\Support\Carbon $date
 * @property string|null $location
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\City $city
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityEvent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityEvent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityEvent query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityEvent whereCityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityEvent whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityEvent whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityEvent whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityEvent whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityEvent whereLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityEvent whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityEvent whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityEvent whereUpdatedAt($value)
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class CityEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'city_id', 'name', 'type', 'date', 'location', 'description',
    ];

    protected $casts = [
        'type' => 'array',
        // 'location' => 'array',
        'date' => 'date:Y-m-d',
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
