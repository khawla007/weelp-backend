<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $place_id
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property string|null $keywords
 * @property string|null $og_image_url
 * @property string|null $canonical_url
 * @property string|null $schema_type
 * @property string|null $schema_data
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Place $place
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceSeo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceSeo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceSeo query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceSeo whereCanonicalUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceSeo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceSeo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceSeo whereKeywords($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceSeo whereMetaDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceSeo whereMetaTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceSeo whereOgImageUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceSeo wherePlaceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceSeo whereSchemaData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceSeo whereSchemaType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceSeo whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class PlaceSeo extends Model
{
    use HasFactory;

    protected $table = 'place_seo';

    protected $fillable = [
        'place_id',
        'meta_title',
        'meta_description',
        'keywords',
        'og_image_url',
        'canonical_url',
        'schema_type',
        'schema_data',
    ];

    // protected $casts = [
    //     'schema_data' => 'array',
    // ];
    public function setSchemaDataAttribute($value)
    {
        $this->attributes['schema_data'] = json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    // Retrieve JSON as array
    public function getSchemaDataAttribute($value)
    {
        return json_decode($value, true);
    }

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }
}
