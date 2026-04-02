<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $schedule_id
 * @property int $transfer_id
 * @property string|null $start_time
 * @property string|null $end_time
 * @property string|null $notes
 * @property numeric|null $price
 * @property bool $included
 * @property string|null $pickup_location
 * @property string|null $dropoff_location
 * @property int|null $pax
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ItinerarySchedule $schedule
 * @property-read \App\Models\Transfer $transfer
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryTransfer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryTransfer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryTransfer query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryTransfer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryTransfer whereDropoffLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryTransfer whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryTransfer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryTransfer whereIncluded($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryTransfer whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryTransfer wherePax($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryTransfer wherePickupLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryTransfer wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryTransfer whereScheduleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryTransfer whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryTransfer whereTransferId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryTransfer whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ItineraryTransfer extends Model
{
    protected $fillable = [
        'schedule_id', 'transfer_id', 'start_time', 'end_time',
        'notes', 'price', 'included',
        'pickup_location', 'dropoff_location', 'pax',
    ];

    protected $casts = [
        'included' => 'boolean',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ItinerarySchedule::class, 'schedule_id');
    }

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(Transfer::class);
    }
}
