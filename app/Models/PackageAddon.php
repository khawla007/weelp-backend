<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $package_id
 * @property int $addon_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Addon $addon
 * @property-read \App\Models\Package $package
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageAddon newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageAddon newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageAddon query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageAddon whereAddonId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageAddon whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageAddon whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageAddon wherePackageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageAddon whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class PackageAddon extends Model
{
    protected $table = 'package_addons';

    protected $fillable = [
        'package_id',
        'addon_id',
    ];

    // Relations
    public function package()
    {
        return $this->belongsTo(Package::class, 'package_id');
    }

    public function addon()
    {
        return $this->belongsTo(Addon::class, 'addon_id');
    }
}
