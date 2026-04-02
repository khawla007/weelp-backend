<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $package_id
 * @property int $category_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Category $category
 * @property-read \App\Models\Package $package
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageCategory whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageCategory wherePackageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageCategory whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class PackageCategory extends Model
{
    // protected $table = 'itinerary_category';

    protected $fillable = [
        'package_id', 'category_id'
    ];

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
