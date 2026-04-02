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
 * @property-read \App\Models\PackageBasePricing $basePricing
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageBlackoutDate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageBlackoutDate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageBlackoutDate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageBlackoutDate whereBasePricingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageBlackoutDate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageBlackoutDate whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageBlackoutDate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageBlackoutDate whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageBlackoutDate whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class PackageBlackoutDate extends Model
{
    protected $fillable = [
        'base_pricing_id', 'date', 'reason',
    ];

    public function basePricing()
    {
        return $this->belongsTo(PackageBasePricing::class, 'base_pricing_id');
    }
}
