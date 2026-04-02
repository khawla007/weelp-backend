<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $schedule_id
 * @property int $transfer_id
 * @property string|null $start_time
 * @property string|null $end_time
 * @property string|null $notes
 * @property numeric|null $price
 * @property bool $included
 * @property string $pickup_location
 * @property string $dropoff_location
 * @property int|null $pax
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\PackageSchedule $schedule
 * @property-read \App\Models\Transfer $transfer
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageTransfer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageTransfer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageTransfer query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageTransfer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageTransfer whereDropoffLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageTransfer whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageTransfer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageTransfer whereIncluded($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageTransfer whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageTransfer wherePax($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageTransfer wherePickupLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageTransfer wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageTransfer whereScheduleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageTransfer whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageTransfer whereTransferId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageTransfer whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class PackageTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'schedule_id',
        'transfer_id',
        'start_time',
        'end_time',
        'notes',
        'price',
        'included',
        'pickup_location',
        'dropoff_location',
        'pax',
    ];

    protected $casts = [
        'included' => 'boolean'
    ];

    public function schedule()
    {
        return $this->belongsTo(PackageSchedule::class, 'schedule_id');
    }

    public function transfer()
    {
        return $this->belongsTo(Transfer::class);
    }
}
