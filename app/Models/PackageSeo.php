<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $package_id
 * @property string $meta_title
 * @property string|null $meta_description
 * @property string|null $keywords
 * @property string|null $og_image_url
 * @property string|null $canonical_url
 * @property string|null $schema_type
 * @property array<array-key, mixed>|null $schema_data
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Package $package
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageSeo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageSeo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageSeo query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageSeo whereCanonicalUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageSeo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageSeo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageSeo whereKeywords($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageSeo whereMetaDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageSeo whereMetaTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageSeo whereOgImageUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageSeo wherePackageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageSeo whereSchemaData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageSeo whereSchemaType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageSeo whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class PackageSeo extends Model
{
    protected $table = 'package_seo';

    protected $fillable = [
        'package_id', 'meta_title', 'meta_description',
        'keywords', 'og_image_url', 'canonical_url',
        'schema_type', 'schema_data',
    ];

    protected $casts = [
        'schema_data' => 'array',
    ];

    public function package()
    {
        return $this->belongsTo(Package::class);
    }
}
