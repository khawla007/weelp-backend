<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $activity_id
 * @property string $promo_code
 * @property int $max_uses
 * @property numeric $discount_amount
 * @property string $discount_type
 * @property string $valid_from
 * @property string $valid_to
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Activity $activity
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityPromoCode newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityPromoCode newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityPromoCode query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityPromoCode whereActivityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityPromoCode whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityPromoCode whereDiscountAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityPromoCode whereDiscountType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityPromoCode whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityPromoCode whereMaxUses($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityPromoCode wherePromoCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityPromoCode whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityPromoCode whereValidFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityPromoCode whereValidTo($value)
 *
 * @mixin \Eloquent
 */
class ActivityPromoCode extends Model
{
    use HasFactory;

    protected $fillable = ['activity_id', 'promo_code', 'max_uses', 'discount_amount', 'discount_type', 'valid_from', 'valid_to'];

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }
}
