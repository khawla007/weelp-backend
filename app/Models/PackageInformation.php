<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $package_id
 * @property string $section_title
 * @property string $content
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Package $package
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageInformation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageInformation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageInformation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageInformation whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageInformation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageInformation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageInformation wherePackageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageInformation whereSectionTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageInformation whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class PackageInformation extends Model
{
    use HasFactory;

    protected $fillable = [
        'package_id',
        'section_title',
        'content',
    ];

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }
}
