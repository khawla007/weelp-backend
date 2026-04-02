<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $package_id
 * @property int $date_based_package
 * @property string|null $start_date
 * @property string|null $end_date
 * @property int $quantity_based_package
 * @property int|null $max_quantity
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Package $package
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageAvailability newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageAvailability newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageAvailability query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageAvailability whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageAvailability whereDateBasedPackage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageAvailability whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageAvailability whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageAvailability whereMaxQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageAvailability wherePackageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageAvailability whereQuantityBasedPackage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageAvailability whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageAvailability whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class PackageAvailability extends Model
{
    use HasFactory;

    protected $fillable = [
        'package_id',
        'date_based_package',
        'start_date',
        'end_date',
        'quantity_based_package',
        'max_quantity',
    ];

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }
}
