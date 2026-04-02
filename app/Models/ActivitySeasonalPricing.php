<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $activity_id
 * @property bool $enable_seasonal_pricing
 * @property string $season_name
 * @property string $season_start
 * @property string $season_end
 * @property numeric $season_price
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Activity $activity
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivitySeasonalPricing newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivitySeasonalPricing newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivitySeasonalPricing query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivitySeasonalPricing whereActivityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivitySeasonalPricing whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivitySeasonalPricing whereEnableSeasonalPricing($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivitySeasonalPricing whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivitySeasonalPricing whereSeasonEnd($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivitySeasonalPricing whereSeasonName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivitySeasonalPricing whereSeasonPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivitySeasonalPricing whereSeasonStart($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivitySeasonalPricing whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ActivitySeasonalPricing extends Model {
    use HasFactory;

    protected $table = 'activity_seasonal_pricing';
    protected $fillable = ['activity_id', 'enable_seasonal_pricing', 'season_name', 'season_start', 'season_end', 'season_price'];

    protected $casts = [
        'enable_seasonal_pricing' => 'boolean'
    ];

    public function activity() {
        return $this->belongsTo(Activity::class);
    }
}
