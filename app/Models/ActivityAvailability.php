<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $activity_id
 * @property int $date_based_activity
 * @property string|null $start_date
 * @property string|null $end_date
 * @property int $quantity_based_activity
 * @property int|null $max_quantity
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Activity $activity
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityAvailability newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityAvailability newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityAvailability query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityAvailability whereActivityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityAvailability whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityAvailability whereDateBasedActivity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityAvailability whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityAvailability whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityAvailability whereMaxQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityAvailability whereQuantityBasedActivity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityAvailability whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityAvailability whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ActivityAvailability extends Model
{
    // protected $table = 'activity_availability';
    protected $fillable = [
        'activity_id',
        'date_based_activity',
        'start_date',
        'end_date',
        'quantity_based_activity',
        'max_quantity',
    ];

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }
}
