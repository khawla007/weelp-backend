<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $schedule_id
 * @property int $itinerary_id
 * @property string|null $start_time
 * @property string|null $end_time
 * @property string|null $notes
 * @property numeric|null $price
 * @property bool $included
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Itinerary $itinerary
 * @property-read \App\Models\PackageSchedule $schedule
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageItinerary newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageItinerary newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageItinerary query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageItinerary whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageItinerary whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageItinerary whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageItinerary whereIncluded($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageItinerary whereItineraryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageItinerary whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageItinerary wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageItinerary whereScheduleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageItinerary whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageItinerary whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class PackageItinerary extends Model
{
    use HasFactory;

    protected $fillable = [
        'schedule_id',
        'itinerary_id',
        'start_time',
        'end_time',
        'notes',
        'price',
        'included',
    ];

    protected $casts = [
        'included' => 'boolean',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(PackageSchedule::class);
    }

    public function itinerary(): BelongsTo
    {
        return $this->belongsTo(Itinerary::class);
    }
}
