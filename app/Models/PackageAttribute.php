<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $package_id
 * @property int $attribute_id
 * @property string $attribute_value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Attribute $attribute
 * @property-read \App\Models\Package $package
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageAttribute newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageAttribute newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageAttribute query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageAttribute whereAttributeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageAttribute whereAttributeValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageAttribute whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageAttribute whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageAttribute wherePackageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageAttribute whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class PackageAttribute extends Model
{
    protected $fillable = [
        'package_id', 'attribute_id', 'attribute_value',
    ];

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function attribute()
    {
        return $this->belongsTo(Attribute::class, 'attribute_id');
    }
}
