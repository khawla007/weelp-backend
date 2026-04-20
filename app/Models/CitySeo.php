<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $city_id
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property string|null $keywords
 * @property string|null $og_image_url
 * @property string|null $canonical_url
 * @property string|null $schema_type
 * @property string|null $schema_data
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\City $city
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CitySeo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CitySeo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CitySeo query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CitySeo whereCanonicalUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CitySeo whereCityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CitySeo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CitySeo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CitySeo whereKeywords($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CitySeo whereMetaDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CitySeo whereMetaTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CitySeo whereOgImageUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CitySeo whereSchemaData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CitySeo whereSchemaType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CitySeo whereUpdatedAt($value)
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class CitySeo extends Model
{
    use HasFactory;

    protected $table = 'city_seo';

    protected $fillable = [
        'city_id', 'meta_title', 'meta_description', 'keywords',
        'og_image_url', 'canonical_url', 'schema_type', 'schema_data',
    ];

    // protected $casts = [
    //     'schema_data' => 'json'
    // ];

    // Store JSON as raw JSON (Not as escaped string)
    public function setSchemaDataAttribute($value)
    {
        $this->attributes['schema_data'] = json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    // Retrieve JSON as array
    public function getSchemaDataAttribute($value)
    {
        return $value ? json_decode($value, true) : null;
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
