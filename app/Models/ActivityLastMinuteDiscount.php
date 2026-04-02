<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $activity_id
 * @property bool $enabled
 * @property int $days_before_start
 * @property numeric $discount_amount
 * @property string $discount_type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Activity $activity
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLastMinuteDiscount newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLastMinuteDiscount newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLastMinuteDiscount query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLastMinuteDiscount whereActivityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLastMinuteDiscount whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLastMinuteDiscount whereDaysBeforeStart($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLastMinuteDiscount whereDiscountAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLastMinuteDiscount whereDiscountType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLastMinuteDiscount whereEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLastMinuteDiscount whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLastMinuteDiscount whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ActivityLastMinuteDiscount extends Model {
    use HasFactory;

    protected $fillable = ['activity_id', 'enabled', 'days_before_start', 'discount_amount', 'discount_type'];

    protected $casts = [
        'enabled' => 'boolean'
    ];

    public function activity() {
        return $this->belongsTo(Activity::class);
    }
}
