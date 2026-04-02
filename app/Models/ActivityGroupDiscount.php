<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $activity_id
 * @property int $min_people
 * @property numeric $discount_amount
 * @property string $discount_type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Activity $activity
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityGroupDiscount newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityGroupDiscount newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityGroupDiscount query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityGroupDiscount whereActivityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityGroupDiscount whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityGroupDiscount whereDiscountAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityGroupDiscount whereDiscountType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityGroupDiscount whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityGroupDiscount whereMinPeople($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityGroupDiscount whereUpdatedAt($value)
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class ActivityGroupDiscount extends Model
{
    use HasFactory;

    protected $fillable = ['activity_id', 'min_people', 'discount_amount', 'discount_type'];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}
