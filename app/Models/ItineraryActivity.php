<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $schedule_id
 * @property int $activity_id
 * @property string|null $start_time
 * @property string|null $end_time
 * @property string|null $notes
 * @property numeric|null $price
 * @property bool $included
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Activity $activity
 * @property-read \App\Models\ItinerarySchedule $schedule
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryActivity newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryActivity newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryActivity query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryActivity whereActivityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryActivity whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryActivity whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryActivity whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryActivity whereIncluded($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryActivity whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryActivity wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryActivity whereScheduleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryActivity whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryActivity whereUpdatedAt($value)
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class ItineraryActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'schedule_id', 'activity_id', 'start_time', 'end_time',
        'notes', 'price', 'included',
    ];

    protected $casts = [
        'included' => 'boolean',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ItinerarySchedule::class, 'schedule_id');
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}
