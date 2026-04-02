<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $activity_id
 * @property numeric $regular_price
 * @property string $currency
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Activity $activity
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityPricing newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityPricing newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityPricing query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityPricing whereActivityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityPricing whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityPricing whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityPricing whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityPricing whereRegularPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityPricing whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ActivityPricing extends Model
{
    use HasFactory;

    protected $table = 'activity_pricing';

    protected $fillable = [
        'activity_id', 'regular_price', 'currency',
    ];

    // protected $casts = [
    //     'enable_seasonal_pricing' => 'boolean',
    //     'enable_early_bird_discount' => 'boolean',
    //     'enable_last_minute_discount' => 'boolean'
    // ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}
