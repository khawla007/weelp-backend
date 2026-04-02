<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $package_id
 * @property int $city_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\City $city
 * @property-read \App\Models\Package $package
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageLocation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageLocation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageLocation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageLocation whereCityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageLocation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageLocation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageLocation wherePackageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageLocation whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class PackageLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'package_id',
        'city_id',
    ];

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function city(): BelongsTo
    {

        return $this->belongsTo(City::class, 'city_id');
    }
}
