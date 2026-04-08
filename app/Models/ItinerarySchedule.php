<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $itinerary_id
 * @property int $day
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ItineraryActivity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\Itinerary $itinerary
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ItineraryTransfer> $transfers
 * @property-read int|null $transfers_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItinerarySchedule newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItinerarySchedule newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItinerarySchedule query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItinerarySchedule whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItinerarySchedule whereDay($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItinerarySchedule whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItinerarySchedule whereItineraryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItinerarySchedule whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ItinerarySchedule extends Model
{
    protected $fillable = [
        'itinerary_id', 'day', 'title'
    ];

    public function itinerary()
    {
        return $this->belongsTo(Itinerary::class);
    }

    public function activities()
    {
        return $this->hasMany(ItineraryActivity::class, 'schedule_id');
    }

    public function transfers()
    {
        return $this->hasMany(ItineraryTransfer::class, 'schedule_id');
    }
}
