<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $package_id
 * @property string $type
 * @property string $title
 * @property string|null $description
 * @property bool $included
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Package $package
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageInclusionExclusion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageInclusionExclusion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageInclusionExclusion query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageInclusionExclusion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageInclusionExclusion whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageInclusionExclusion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageInclusionExclusion whereIncluded($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageInclusionExclusion wherePackageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageInclusionExclusion whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageInclusionExclusion whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageInclusionExclusion whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class PackageInclusionExclusion extends Model
{
    protected $table = 'package_inclusions_exclusions';

    protected $fillable = [
        'package_id', 'type', 'title',
        'description', 'included',
    ];

    protected $casts = [
        'included' => 'boolean',
    ];

    public function package()
    {
        return $this->belongsTo(Package::class);
    }
}
