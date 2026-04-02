<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $package_id
 * @property string $currency
 * @property string $availability
 * @property string|null $start_date
 * @property string|null $end_date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PackageBlackoutDate> $blackoutDates
 * @property-read int|null $blackout_dates_count
 * @property-read \App\Models\Package $package
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PackagePriceVariation> $variations
 * @property-read int|null $variations_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageBasePricing newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageBasePricing newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageBasePricing query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageBasePricing whereAvailability($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageBasePricing whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageBasePricing whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageBasePricing whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageBasePricing whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageBasePricing wherePackageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageBasePricing whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageBasePricing whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class PackageBasePricing extends Model
{
    protected $table = 'package_base_pricing';
    
    protected $fillable = [
        'package_id', 'currency', 'availability', 
        'start_date', 'end_date'
    ];

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function variations()
    {
        return $this->hasMany(PackagePriceVariation::class, 'base_pricing_id');
    }

    public function blackoutDates()
    {
        return $this->hasMany(PackageBlackoutDate::class, 'base_pricing_id');
    }
}
