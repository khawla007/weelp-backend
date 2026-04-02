<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $place_id
 * @property string $name
 * @property array<array-key, mixed> $type
 * @property \Illuminate\Support\Carbon|null $date
 * @property string $location
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Place $place
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceEvent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceEvent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceEvent query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceEvent whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceEvent whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceEvent whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceEvent whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceEvent whereLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceEvent whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceEvent wherePlaceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceEvent whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceEvent whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class PlaceEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'place_id',
        'name',
        'type',
        'date',
        'location',
        'description',
    ];

    protected $casts = [
        'type' => 'array',
        // 'location' => 'array',
        'date' => 'date:Y-m-d',
    ];

    public function place()
    {
        return $this->belongsTo(Place::class);
    }
}
