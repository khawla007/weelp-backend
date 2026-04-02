<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $package_id
 * @property int $media_id
 * @property int $is_featured
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Media $media
 * @property-read \App\Models\Package $package
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageMediaGallery featured()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageMediaGallery newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageMediaGallery newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageMediaGallery query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageMediaGallery whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageMediaGallery whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageMediaGallery whereIsFeatured($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageMediaGallery whereMediaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageMediaGallery wherePackageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageMediaGallery whereUpdatedAt($value)
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class PackageMediaGallery extends Model
{
    protected $table = 'package_media_gallery';

    protected $fillable = [
        'package_id', 'media_id', 'is_featured',
    ];

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'media_id');
    }
}
