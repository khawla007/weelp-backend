<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityEarlyBirdDiscount newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityEarlyBirdDiscount newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityEarlyBirdDiscount query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityEarlyBirdDiscount whereActivityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityEarlyBirdDiscount whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityEarlyBirdDiscount whereDaysBeforeStart($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityEarlyBirdDiscount whereDiscountAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityEarlyBirdDiscount whereDiscountType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityEarlyBirdDiscount whereEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityEarlyBirdDiscount whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityEarlyBirdDiscount whereUpdatedAt($value)
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class ActivityEarlyBirdDiscount extends Model
{
    use HasFactory;

    protected $fillable = ['activity_id', 'enabled', 'days_before_start', 'discount_amount', 'discount_type'];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}
