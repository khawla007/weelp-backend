<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $base_pricing_id
 * @property string $date
 * @property string|null $reason
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ItineraryBasePricing $basePricing
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryBlackoutDate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryBlackoutDate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryBlackoutDate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryBlackoutDate whereBasePricingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryBlackoutDate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryBlackoutDate whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryBlackoutDate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryBlackoutDate whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryBlackoutDate whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ItineraryBlackoutDate extends Model
{
    protected $fillable = [
        'base_pricing_id', 'date', 'reason',
    ];

    public function basePricing()
    {
        return $this->belongsTo(ItineraryBasePricing::class, 'base_pricing_id');
    }
}
