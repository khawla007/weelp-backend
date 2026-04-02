<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $package_id
 * @property int $tag_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Package $package
 * @property-read \App\Models\Tag $tag
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageTag newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageTag newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageTag query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageTag whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageTag whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageTag wherePackageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageTag whereTagId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageTag whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class PackageTag extends Model
{
    protected $fillable = [
        'package_id', 'tag_id',
    ];

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function tag()
    {
        return $this->belongsTo(Tag::class, 'tag_id');
    }
}
