<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
 * @property-read \App\Models\PackageSchedule $schedule
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageActivity newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageActivity newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageActivity query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageActivity whereActivityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageActivity whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageActivity whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageActivity whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageActivity whereIncluded($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageActivity whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageActivity wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageActivity whereScheduleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageActivity whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageActivity whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class PackageActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'schedule_id',
        'activity_id',
        'start_time',
        'end_time',
        'notes',
        'price',
        'included',
    ];

    protected $casts = [
        'included' => 'boolean',
    ];

    public function schedule()
    {
        return $this->belongsTo(PackageSchedule::class);
    }

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }
}
