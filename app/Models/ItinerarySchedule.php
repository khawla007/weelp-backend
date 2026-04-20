<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $itinerary_id
 * @property int $day
 * @property string|null $title
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ItineraryActivity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\Itinerary $itinerary
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ItineraryTransfer> $transfers
 * @property-read int|null $transfers_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItinerarySchedule newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItinerarySchedule newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItinerarySchedule query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItinerarySchedule whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItinerarySchedule whereDay($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItinerarySchedule whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItinerarySchedule whereItineraryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItinerarySchedule whereUpdatedAt($value)
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class ItinerarySchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'itinerary_id', 'day', 'title',
    ];

    public function itinerary(): BelongsTo
    {
        return $this->belongsTo(Itinerary::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ItineraryActivity::class, 'schedule_id');
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(ItineraryTransfer::class, 'schedule_id');
    }
}
