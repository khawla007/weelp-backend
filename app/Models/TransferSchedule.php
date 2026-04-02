<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $transfer_id
 * @property bool $is_vendor
 * @property string|null $availability_type
 * @property array<array-key, mixed>|null $available_days
 * @property array<array-key, mixed>|null $time_slots
 * @property array<array-key, mixed>|null $blackout_dates
 * @property int|null $minimum_lead_time
 * @property int|null $maximum_passengers
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Transfer $transfer
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferSchedule newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferSchedule newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferSchedule query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferSchedule whereAvailabilityType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferSchedule whereAvailableDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferSchedule whereBlackoutDates($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferSchedule whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferSchedule whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferSchedule whereIsVendor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferSchedule whereMaximumPassengers($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferSchedule whereMinimumLeadTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferSchedule whereTimeSlots($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferSchedule whereTransferId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferSchedule whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class TransferSchedule extends Model
{
    use HasFactory;

    protected $table = 'transfer_schedules';

    protected $fillable = [
        'transfer_id',
        'is_vendor',
        'availability_type',
        'available_days',
        'time_slots',
        'blackout_dates',
        'minimum_lead_time',
        'maximum_passengers',
    ];

    protected $casts = [
        'is_vendor' => 'boolean',
        'available_days' => 'array',
        'time_slots' => 'array',
        'blackout_dates' => 'array',
        'minimum_lead_time' => 'integer',
        'maximum_passengers' => 'integer',
    ];

    public function transfer()
    {
        return $this->belongsTo(Transfer::class);
    }
}
